import { BarChart4, Menu } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { UserRole } from '@/hooks/useTodoStore';

interface HeaderProps {
  currentRole: UserRole;
  onRoleChange: (role: UserRole) => void;
  currentUser: string;
  onUserChange: (user: string) => void;
}

export const Header = ({
  currentRole,
  onRoleChange,
  currentUser,
  onUserChange,
}: HeaderProps) => {
  const displayRole = currentRole === 'main-manager' ? 'Main Manager' : 'Processing Manager';

  return (
    <header className="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/95 backdrop-blur">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex h-16 items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-blue-700">
              <BarChart4 className="w-6 h-6 text-white" />
            </div>
            <div className="hidden sm:block">
              <h1 className="text-xl font-bold text-slate-900">TaskFlow</h1>
              <p className="text-xs text-slate-500">Processing Manager</p>
            </div>
          </div>

          <div className="flex items-center gap-4">
            <div className="hidden sm:block">
              <Select value={currentRole} onValueChange={onRoleChange}>
                <SelectTrigger className="w-44">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="main-manager">Main Manager</SelectItem>
                  <SelectItem value="processing-manager">Processing Manager</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="hidden sm:block h-6 w-px bg-slate-200" />

            <div className="hidden sm:block">
              <Select value={currentUser} onValueChange={onUserChange}>
                <SelectTrigger className="w-40">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="John Manager">John Manager</SelectItem>
                  <SelectItem value="Alice Developer">Alice Developer</SelectItem>
                  <SelectItem value="Bob Developer">Bob Developer</SelectItem>
                  <SelectItem value="Carol Designer">Carol Designer</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button variant="ghost" size="icon" className="sm:hidden">
              <Menu className="w-5 h-5" />
            </Button>
          </div>
        </div>
      </div>
    </header>
  );
};
