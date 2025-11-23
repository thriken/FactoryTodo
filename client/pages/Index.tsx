import { useMemo } from 'react';
import { Header } from '@/components/Header';
import { AddTodoDialog } from '@/components/AddTodoDialog';
import { TodoCard } from '@/components/TodoCard';
import { useTodoStore } from '@/hooks/useTodoStore';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-react';

export default function Index() {
  const {
    todos,
    currentRole,
    setCurrentRole,
    currentUser,
    setCurrentUser,
    addTodo,
    updateStepStatus,
    deleteTodo,
  } = useTodoStore();

  const isMainManager = currentRole === 'main-manager';

  const filteredTodos = useMemo(() => {
    if (isMainManager) {
      return todos;
    }

    const userFirstName = currentUser.split(' ')[0];
    return todos.filter((todo) =>
      todo.steps.some((step) => step.status === 'pending' || step.status === 'in-progress')
    );
  }, [todos, currentRole, currentUser, isMainManager]);

  const stats = {
    total: todos.length,
    completed: todos.filter((t) => t.status === 'completed').length,
    inProgress: todos.filter((t) => t.status === 'in-progress').length,
    pending: todos.filter((t) => t.status === 'pending').length,
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <Header
        currentRole={currentRole}
        onRoleChange={setCurrentRole}
        currentUser={currentUser}
        onUserChange={setCurrentUser}
      />

      <main className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
              <h2 className="text-3xl font-bold text-slate-900">Processing Dashboard</h2>
              <p className="text-sm text-slate-600 mt-1">
                {isMainManager
                  ? 'Manage and assign tasks to your processing team'
                  : 'Track and complete assigned processing steps'}
              </p>
            </div>
            {isMainManager && (
              <AddTodoDialog onAdd={addTodo} disabled={false} />
            )}
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div className="bg-white rounded-lg border border-slate-200 p-4">
              <p className="text-sm font-medium text-slate-600">Total Todos</p>
              <p className="text-2xl font-bold text-slate-900 mt-2">{stats.total}</p>
            </div>
            <div className="bg-white rounded-lg border border-slate-200 p-4">
              <p className="text-sm font-medium text-slate-600">In Progress</p>
              <p className="text-2xl font-bold text-blue-600 mt-2">{stats.inProgress}</p>
            </div>
            <div className="bg-white rounded-lg border border-slate-200 p-4">
              <p className="text-sm font-medium text-slate-600">Pending</p>
              <p className="text-2xl font-bold text-amber-600 mt-2">{stats.pending}</p>
            </div>
            <div className="bg-white rounded-lg border border-slate-200 p-4">
              <p className="text-sm font-medium text-slate-600">Completed</p>
              <p className="text-2xl font-bold text-green-600 mt-2">{stats.completed}</p>
            </div>
          </div>

          <div className="space-y-4">
            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
              <div className="relative flex-1 w-full">
                <Search className="absolute left-3 top-3 w-5 h-5 text-slate-400" />
                <Input
                  placeholder="Search todos..."
                  className="pl-10 border-slate-200"
                  disabled
                />
              </div>
              <div className="flex gap-2 w-full sm:w-auto">
                <Badge variant="outline" className="px-3 py-2 cursor-pointer hover:bg-slate-100 transition-colors">
                  All
                </Badge>
                <Badge variant="outline" className="px-3 py-2 cursor-pointer hover:bg-slate-100 transition-colors">
                  Active
                </Badge>
                <Badge variant="outline" className="px-3 py-2 cursor-pointer hover:bg-slate-100 transition-colors">
                  Completed
                </Badge>
              </div>
            </div>
          </div>

          {filteredTodos.length === 0 ? (
            <div className="text-center py-12">
              <div className="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <svg
                  className="w-8 h-8 text-slate-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <h3 className="text-lg font-semibold text-slate-900">No todos yet</h3>
              <p className="text-slate-600 mt-1">
                {isMainManager
                  ? 'Create your first todo to get started'
                  : 'Waiting for your manager to assign tasks'}
              </p>
            </div>
          ) : (
            <div className="grid gap-4">
              {filteredTodos.map((todo) => (
                <TodoCard
                  key={todo.id}
                  todo={todo}
                  canEdit={isMainManager}
                  currentRole={currentRole}
                  onUpdateStep={(stepId, newStatus) =>
                    updateStepStatus(todo.id, stepId, newStatus)
                  }
                  onDelete={deleteTodo}
                />
              ))}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
