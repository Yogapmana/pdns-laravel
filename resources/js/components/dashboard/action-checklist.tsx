import { Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2 } from 'lucide-react';
import type { TindakanItem } from '@/pages/admin/dashboard';

export function ActionChecklist({ items }: { items: TindakanItem[] }) {
    if (!items || items.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-10 text-center">
                <CheckCircle2 className="h-10 w-10 text-success mb-3 opacity-80" />
                <h4 className="text-[15px] font-semibold text-navy">Semua Aman!</h4>
                <p className="text-sm text-muted-foreground mt-1 max-w-[250px]">
                    Tidak ada tindakan mendesak yang memerlukan perhatian Anda saat ini.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between mb-2">
                <h4 className="text-[15px] font-semibold text-navy">Perlu Tindakan</h4>
                <span className="text-xs font-medium px-2 py-0.5 rounded-full bg-rose-100 text-danger">
                    {items.length} item mendesak
                </span>
            </div>
            <div className="space-y-3">
                {items.map((item) => {
                    const dotColor = item.priority === 'high' ? 'bg-rose-500 shadow-rose-200' : item.priority === 'medium' ? 'bg-amber-500 shadow-amber-200' : 'bg-emerald-500 shadow-emerald-200';

                    return (
                        <Link 
                            key={item.id} 
                            href={item.href} 
                            className="flex gap-3 p-4 rounded-2xl bg-slate-50/50 hover:bg-white border border-slate-100 hover:border-slate-200 hover:shadow-sm transition-all group"
                        >
                            <div className={`w-2.5 h-2.5 rounded-full ${dotColor} mt-1.5 flex-shrink-0 shadow-sm`} />
                            <div className="flex-1 min-w-0">
                                <p className="text-[14px] font-bold text-navy group-hover:text-primary transition flex items-center justify-between gap-2">
                                    <span className="truncate">{item.title}</span>
                                    <ArrowRight className="h-4 w-4 opacity-0 group-hover:opacity-100 transition-all transform group-hover:translate-x-1 flex-shrink-0 text-primary" />
                                </p>
                                <p className="text-xs text-muted-foreground mt-1 leading-relaxed">
                                    {item.description}
                                </p>
                            </div>
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
