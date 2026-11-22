export type AlarmSeverity = 'critical' | 'warning' | 'info';

export type AlarmDefinition = {
    label: string;
    meaning: string;
    trigger: string;
    severity: AlarmSeverity;
};

export const EVENT_DEFINITIONS: Record<string, AlarmDefinition> = {
    ignitionOn: {
        label: 'Ignition On',
        meaning: 'Vehicle engine or accessory power turned on.',
        trigger: 'ACC ignition wire active, RPM > 0, or alternator voltage > 13.2V.',
        severity: 'info',
    },
    ignitionOff: {
        label: 'Ignition Off',
        meaning: 'Vehicle engine switched off.',
        trigger: 'ACC ignition wire off and engine RPM drops to 0.',
        severity: 'info',
    },
    deviceMoving: {
        label: 'Vehicle Moving',
        meaning: 'Vehicle started moving or resumed a trip.',
        trigger: 'GPS speed > 5 km/h or motion sensor activated.',
        severity: 'info',
    },
    deviceStopped: {
        label: 'Vehicle Stopped',
        meaning: 'Vehicle came to a complete halt.',
        trigger: 'Speed <= 5 km/h sustained for minimum stop duration.',
        severity: 'info',
    },
    deviceOverspeed: {
        label: 'Overspeeding',
        meaning: 'Vehicle exceeded the configured speed limit.',
        trigger: 'Reported speed > allowed speed limit threshold.',
        severity: 'critical',
    },
    geofenceEnter: {
        label: 'Geofence Enter',
        meaning: 'Vehicle entered a designated geofence zone.',
        trigger: 'GPS coordinates crossed inside geofence boundary.',
        severity: 'info',
    },
    geofenceExit: {
        label: 'Geofence Exit',
        meaning: 'Vehicle exited a designated geofence zone.',
        trigger: 'GPS coordinates crossed outside geofence boundary.',
        severity: 'warning',
    },
    maintenance: {
        label: 'Maintenance Due',
        meaning: 'Scheduled maintenance interval reached.',
        trigger: 'Remaining service distance <= 500 km or time interval elapsed.',
        severity: 'warning',
    },
    queuedCommandSent: {
        label: 'Command Sent',
        meaning: 'Queued remote command delivered to tracker.',
        trigger: 'Device came online and acknowledged pending command.',
        severity: 'info',
    },
    commandResult: {
        label: 'Command Response',
        meaning: 'Tracker replied to a remote configuration command.',
        trigger: 'Device returned command execution output.',
        severity: 'info',
    },
    alarm: {
        label: 'Hardware Alarm',
        meaning: 'Tracker triggered a security or safety alarm.',
        trigger: 'Hardware sensor threshold breached on device.',
        severity: 'critical',
    },
};

export const ALARM_SUBTYPE_DEFINITIONS: Record<string, AlarmDefinition> = {
    vibration: {
        label: 'Vibration / Shock',
        meaning: 'Physical impact or vibration detected while parked.',
        trigger: 'Accelerometer G-sensor exceeded threshold while Ignition is OFF.',
        severity: 'warning',
    },
    movement: {
        label: 'Unauthorized Movement',
        meaning: 'Vehicle moved while ignition was switched off.',
        trigger: 'GPS position shifted outside parked radius with Ignition OFF.',
        severity: 'critical',
    },
    tow: {
        label: 'Towing Alert',
        meaning: 'Vehicle is being lifted or towed without ignition.',
        trigger: 'Tilt + GPS displacement detected while Ignition is OFF.',
        severity: 'critical',
    },
    powerOff: {
        label: 'Power Off',
        meaning: 'Tracker powered down completely.',
        trigger: 'Device switched off or internal backup battery depleted.',
        severity: 'critical',
    },
    powerOn: {
        label: 'Power On',
        meaning: 'Tracker booted up and connected.',
        trigger: 'Device power restored or cold boot completed.',
        severity: 'info',
    },
    powerCut: {
        label: 'Main Power Cut / Unplugged',
        meaning: 'External vehicle power lost (OBD unplugged or wire cut).',
        trigger: 'Vehicle supply voltage dropped to 0V; running on backup battery.',
        severity: 'critical',
    },
    powerRestored: {
        label: 'Main Power Restored',
        meaning: 'External vehicle power reconnected.',
        trigger: '12V/24V supply voltage restored to tracker.',
        severity: 'info',
    },
    lowBattery: {
        label: 'Low Tracker Battery',
        meaning: 'Tracker internal backup battery is critically low.',
        trigger: 'Backup battery level dropped below 20%.',
        severity: 'warning',
    },
    lowPower: {
        label: 'Low Vehicle Battery',
        meaning: 'Vehicle starter battery voltage is dangerously low.',
        trigger: 'Vehicle battery voltage dropped below 11.5V.',
        severity: 'warning',
    },
    highCoolantTemp: {
        label: 'High Coolant Temp',
        meaning: 'Engine coolant temperature reached overheating threshold.',
        trigger: 'Coolant temperature >= 105°C.',
        severity: 'critical',
    },
    sos: {
        label: 'SOS Panic Alert',
        meaning: 'Driver triggered the emergency SOS alert.',
        trigger: 'In-cabin SOS panic button pressed.',
        severity: 'critical',
    },
    overspeed: {
        label: 'Overspeed Alarm',
        meaning: 'Vehicle exceeded hardware speed threshold.',
        trigger: 'Speed > configured hardware limit.',
        severity: 'critical',
    },
    hardAcceleration: {
        label: 'Harsh Acceleration',
        meaning: 'Aggressive throttle acceleration detected.',
        trigger: 'Forward G-force exceeded driver behavior threshold.',
        severity: 'warning',
    },
    hardBraking: {
        label: 'Harsh Braking',
        meaning: 'Sudden or emergency braking detected.',
        trigger: 'Deceleration G-force exceeded driver behavior threshold.',
        severity: 'warning',
    },
    hardCornering: {
        label: 'Harsh Cornering',
        meaning: 'Sharp cornering at high speed.',
        trigger: 'Lateral G-force exceeded cornering threshold.',
        severity: 'warning',
    },
    accident: {
        label: 'Crash / Impact',
        meaning: 'Potential collision impact detected.',
        trigger: 'High-G impact spike detected by accelerometer.',
        severity: 'critical',
    },
    removing: {
        label: 'Device Removed',
        meaning: 'OBD tracker was unplugged from the diagnostic port.',
        trigger: 'Physical disconnect switch triggered.',
        severity: 'critical',
    },
    tampering: {
        label: 'Device Tampering',
        meaning: 'Physical tampering with tracker hardware.',
        trigger: 'Casing or tamper sensor triggered.',
        severity: 'critical',
    },
    jamming: {
        label: 'Signal Jamming',
        meaning: 'GPS/GSM signal interference detected.',
        trigger: 'RF jamming detected by cellular modem.',
        severity: 'critical',
    },
};

export function getEventTypeLabel(type: string): string {
    return (
        EVENT_DEFINITIONS[type]?.label ??
        ALARM_SUBTYPE_DEFINITIONS[type]?.label ??
        type
    );
}

export function resolveEventDefinition(event: {
    eventType: string;
    alarm?: string | null;
    attributes?: Record<string, unknown>;
}): AlarmDefinition {
    if (event.alarm && ALARM_SUBTYPE_DEFINITIONS[event.alarm]) {
        return ALARM_SUBTYPE_DEFINITIONS[event.alarm];
    }

    if (EVENT_DEFINITIONS[event.eventType]) {
        return EVENT_DEFINITIONS[event.eventType];
    }

    return {
        label: event.alarm ?? event.eventType,
        meaning: event.attributes?.message
            ? String(event.attributes.message)
            : 'System telematics event logged.',
        trigger: 'Reported by vehicle telematics unit.',
        severity: event.eventType === 'alarm' ? 'critical' : 'info',
    };
}