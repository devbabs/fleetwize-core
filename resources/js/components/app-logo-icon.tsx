import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/images/brand/icon.png"
            alt="Fleetwize"
            {...props}
            className={`rounded-md object-contain ${props.className ?? ''}`}
        />
    );
}
