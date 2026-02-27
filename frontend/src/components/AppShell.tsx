import React, { useState } from 'react';
import { 
  LayoutDashboard, 
  CalendarCheck, 
  CalendarDays, 
  Users, 
  LogOut, 
  PanelLeftClose,
  MessageSquare
} from 'lucide-react';
import { cn } from '../lib/utils';

interface NavItemProps {
  icon: React.ElementType;
  label: string;
  isActive?: boolean;
  onClick: () => void;
  children?: React.ReactNode;
}

const NavItem = ({ icon: Icon, label, isActive, onClick, children }: NavItemProps) => (
  <div className="w-full">
    <button
      onClick={onClick}
      className={cn(
        "flex items-center gap-4 w-full px-8 py-[14px] transition-all relative group",
        isActive 
          ? "bg-sky-50 text-sky-600 font-bold" 
          : "text-slate-600 hover:bg-slate-50"
      )}
    >
      <Icon size={22} className={cn(isActive ? "text-sky-600" : "text-slate-400 group-hover:text-slate-600")} strokeWidth={isActive ? 2.5 : 2} />
      <span className="text-[15px] flex-1 text-left tracking-tight">{label}</span>
      {isActive && (
        <div className="absolute right-0 top-0 bottom-0 w-[5px] bg-sky-600 rounded-l-sm" />
      )}
    </button>
    {isActive && children && (
      <div className="flex flex-col bg-white">
        {children}
      </div>
    )}
  </div>
);

const SubNavItem = ({ label, isActive }: { label: string, isActive?: boolean }) => (
  <button className={cn(
    "pl-[80px] py-3 text-[14px] text-left transition-colors font-medium",
    isActive ? "text-sky-600 font-bold" : "text-slate-500 hover:text-slate-800"
  )}>
    {label}
  </button>
);

export const AppShell = ({ children, roleId }: { children: React.ReactNode, roleId: number }) => {
  const [activeMenu, setActiveMenu] = useState('Attendance');

  return (
    <div className="flex h-screen bg-white font-sans antialiased overflow-hidden">
      {/* Sidebar */}
      <aside className="w-[320px] bg-white border-r border-slate-100 flex flex-col z-20 shadow-[8px_0_32px_rgba(0,0,0,0.02)]">
        {/* Branding */}
        <div className="p-8 pt-10 pb-12 flex items-center gap-3">
          <div className="relative">
            <MessageSquare className="text-sky-700 fill-sky-700" size={32} />
            <div className="absolute inset-0 flex items-center justify-center">
              <div className="w-1.5 h-1.5 bg-white rounded-full -mt-1 ml-1" />
            </div>
          </div>
          <span className="text-[32px] font-black tracking-[-0.05em] text-slate-800 uppercase leading-none">iREPLY</span>
        </div>

        {/* User Profile */}
        <div className="px-8 mb-10 flex items-center gap-4">
          <div className="w-[56px] h-[56px] rounded-full bg-sky-50 border-[3px] border-white shadow-sm ring-1 ring-slate-100 flex-shrink-0" />
          <div className="flex flex-col">
            <div className="text-[16px] font-bold text-slate-900 leading-tight">User Name</div>
            <div className="text-[13px] text-slate-400 font-semibold tracking-wide mt-0.5">Role Name</div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex flex-col flex-1 overflow-y-auto no-scrollbar">
          <NavItem 
            icon={LayoutDashboard} 
            label="Dashboard" 
            isActive={activeMenu === 'Dashboard'} 
            onClick={() => setActiveMenu('Dashboard')} 
          />
          
          <NavItem 
            icon={CalendarCheck} 
            label="Attendance" 
            isActive={activeMenu === 'Attendance'} 
            onClick={() => setActiveMenu('Attendance')}
          >
            {roleId >= 3 && <SubNavItem label="Master Attendance" isActive />}
            {roleId >= 3 && <SubNavItem label="Final Approvals" />}
            <SubNavItem label="My Requests" />
            <SubNavItem label="My Filing Center" />
            <SubNavItem label="My Financial Records" />
          </NavItem>

          <NavItem 
            icon={CalendarDays} 
            label="Schedule" 
            isActive={activeMenu === 'Schedule'} 
            onClick={() => setActiveMenu('Schedule')} 
          />

          <NavItem 
            icon={Users} 
            label="Team" 
            isActive={activeMenu === 'Team'} 
            onClick={() => setActiveMenu('Team')} 
          />
        </nav>

        {/* Footer */}
        <div className="p-6 bg-white flex gap-3 mt-auto border-t border-slate-50">
          <button className="flex-1 flex items-center justify-center gap-3 px-6 py-4 bg-slate-50 text-slate-700 rounded-xl hover:bg-slate-100 transition-all active:scale-[0.98] group">
            <LogOut size={20} className="text-slate-400 group-hover:text-slate-600" />
            <span className="font-bold text-[15px] tracking-tight">Logout</span>
          </button>
          <button className="p-4 bg-slate-50 text-slate-700 rounded-xl hover:bg-slate-100 transition-all active:scale-[0.98] group">
            <PanelLeftClose size={20} className="text-slate-400 group-hover:text-slate-600" />
          </button>
        </div>
      </aside>

      {/* Main Content Area */}
      <main className="flex-1 overflow-y-auto bg-[#FAFBFC]">
        <div className="p-10">
          {children}
        </div>
      </main>
    </div>
  );
};


