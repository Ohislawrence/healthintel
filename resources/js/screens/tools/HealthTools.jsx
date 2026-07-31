import React from 'react';
import { Link } from 'react-router-dom';
import useAuthStore from '../../stores/authStore';

const ALL_CALCULATORS = [
    { icon: '◉', color: '#0EA5E9', title: 'BMI Calculator', subtitle: 'Body Mass Index — estimates body fat based on height and weight', to: '/health-tools/bmi', forSex: null },
    { icon: '▲', color: '#F97316', title: 'BMR & TDEE Calculator', subtitle: 'Basal Metabolic Rate & Total Daily Energy Expenditure', to: '/health-tools/bmr', forSex: null },
    { icon: '⚛', color: '#EC4899', title: 'Due Date Calculator', subtitle: 'Estimate your baby due date & track pregnancy weeks', to: '/health-tools/due-date', forSex: 'female' },
    { icon: '◉', color: '#F97316', title: 'Waist-to-Hip Ratio', subtitle: 'Assess your body fat distribution and health risk', to: '/health-tools/waist-hip', forSex: null },
];

const ALL_TRACKERS = [
    { icon: '⬤', color: '#DC2626', title: 'Blood Pressure Log', subtitle: 'Log systolic & diastolic readings and see trends over time', to: '/health-tools/blood-pressure', forSex: null },
    { icon: '∼', color: '#2563EB', title: 'Water Intake Tracker', subtitle: 'Log your daily water intake, set goals & track progress', to: '/health-tools/water', forSex: null },
    { icon: '●', color: '#16A34A', title: 'Food & Symptom Diary', subtitle: 'Track what you eat and how you feel — spot patterns over time', to: '/health-tools/food-diary', forSex: null },
    { icon: '◷', color: '#EC4899', title: 'Period & Cycle Tracker', subtitle: 'Log periods, track cycles, predict ovulation & fertile windows', to: '/health-tools/period', forSex: 'female' },
    { icon: '◇', color: '#9333EA', title: 'Immunization Tracker', subtitle: "Track your child's vaccines based on NPHCDA schedule", to: '/health-tools/immunization', forSex: null },
    { icon: '◷', color: '#0EA5E9', title: 'Appointment Tracker', subtitle: 'Book, track & get reminded for medical appointments', to: '/health-tools/appointments', forSex: null },
];

function ToolCard({ icon, color, title, subtitle, to }) {
    const content = (
        <div className="card p-4 flex items-center gap-3 hover:shadow-md hover:border-teal-200 transition-all">
            <div className="w-11 h-11 rounded-lg flex items-center justify-center text-xl flex-shrink-0" style={{ backgroundColor: color + '15' }}>
                <span style={{ color }}>{icon}</span>
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-bold text-neutral-900">{title}</p>
                <p className="text-xs text-neutral-500 mt-0.5 line-clamp-2">{subtitle}</p>
            </div>
            <span className="text-xl text-neutral-300 font-bold flex-shrink-0">›</span>
        </div>
    );

    if (to) {
        return <Link to={to}>{content}</Link>;
    }
    return (
        <div className="relative">
            {content}
            <span className="absolute top-2 right-3 text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5 uppercase tracking-wider">
                Soon
            </span>
        </div>
    );
}

export default function HealthTools() {
    const { user } = useAuthStore();
    const sex = user?.health_profile?.sex || null;

    const CALCULATORS = ALL_CALCULATORS.filter(t => !t.forSex || t.forSex === sex);
    const TRACKERS = ALL_TRACKERS.filter(t => !t.forSex || t.forSex === sex);
    return (
        <div className="space-y-5">
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Health Tools</p>
                <p className="text-sm font-medium text-neutral-500 mt-0.5">
                    Calculators & trackers to help you understand and manage your health. Data feeds into our AI for smarter recommendations.
                </p>
            </div>

            <div>
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">Calculators</p>
                <div className="space-y-2">
                    {CALCULATORS.map((tool) => (
                        <ToolCard key={tool.title} {...tool} />
                    ))}
                </div>
            </div>

            <div>
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">Trackers</p>
                <div className="space-y-2">
                    {TRACKERS.map((tracker) => (
                        <ToolCard key={tracker.title} {...tracker} />
                    ))}
                </div>
            </div>

            {/* AI Note */}
            <div className="card p-4 bg-teal-50 border-teal-200 flex gap-2.5 items-start">
                <span className="text-lg text-teal-600 mt-0.5">⬡</span>
                <p className="text-sm text-teal-700 leading-relaxed">
                    Data from your health tools helps our AI provide more personalized recommendations in the Symptom Checker and Lab Result interpretations.
                </p>
            </div>
        </div>
    );
}