import { useState } from 'react';
import { ImageIcon, PlayCircle } from 'lucide-react';

function Viewer({ item, fallbackTitle }) {
    const title = item.title || fallbackTitle;

    if (item.media_type === 'image') {
        return <img src={item.url} alt={title} className="h-full w-full object-cover" />;
    }
    if (item.media_type === 'video_local') {
        return (
            <video key={item.id} src={item.url} controls preload="metadata" playsInline className="h-full w-full bg-black object-contain">
                Tu navegador no puede reproducir este video.
            </video>
        );
    }
    return (
        <iframe
            key={item.id}
            src={item.url}
            title={title}
            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
            allowFullScreen
            loading="lazy"
            className="h-full w-full bg-black"
        />
    );
}

/** Galería de fotos y videos (locales y de YouTube/Vimeo) con miniaturas. */
export default function MediaGallery({ media, serviceName }) {
    const [index, setIndex] = useState(0);

    if (!media?.length) {
        return (
            <div className="flex aspect-video items-center justify-center rounded-2xl bg-secondary text-white/60">
                <ImageIcon className="h-12 w-12" aria-hidden="true" />
                <span className="sr-only">Sin fotos ni videos por ahora</span>
            </div>
        );
    }

    const current = media[Math.min(index, media.length - 1)];

    return (
        <div>
            <div className="aspect-video overflow-hidden rounded-2xl bg-black">
                <Viewer item={current} fallbackTitle={serviceName} />
            </div>
            {media.length > 1 && (
                <ul className="mt-3 flex gap-2 overflow-x-auto pb-1" aria-label="Fotos y videos">
                    {media.map((item, i) => (
                        <li key={item.id} className="shrink-0">
                            <button
                                type="button"
                                onClick={() => setIndex(i)}
                                aria-current={i === index ? 'true' : undefined}
                                aria-label={`${item.media_type === 'image' ? 'Ver foto' : 'Ver video'} ${i + 1} de ${media.length}${item.title ? `: ${item.title}` : ''}`}
                                className={`relative block h-16 w-24 overflow-hidden rounded-xl border-2 transition sm:h-20 sm:w-28 ${
                                    i === index ? 'border-primary' : 'border-transparent opacity-70 hover:opacity-100'
                                }`}
                            >
                                {item.media_type === 'image' ? (
                                    <img src={item.url} alt="" loading="lazy" className="h-full w-full object-cover" />
                                ) : (
                                    <span className="flex h-full w-full items-center justify-center bg-secondary text-white">
                                        <PlayCircle className="h-7 w-7" aria-hidden="true" />
                                    </span>
                                )}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
