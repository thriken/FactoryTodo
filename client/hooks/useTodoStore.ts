import { useState, useCallback } from 'react';

export type UserRole = 'main-manager' | 'processing-manager';
export type TodoStatus = 'pending' | 'in-progress' | 'completed';
export type StepStatus = 'pending' | 'in-progress' | 'completed';

export interface ProcessingStep {
  id: string;
  title: string;
  status: StepStatus;
  completedAt?: Date;
  completedBy?: string;
}

export interface Todo {
  id: string;
  title: string;
  description: string;
  steps: ProcessingStep[];
  status: TodoStatus;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
}

export const useTodoStore = () => {
  const [todos, setTodos] = useState<Todo[]>([
    {
      id: '1',
      title: 'Build Authentication System',
      description: 'Implement user authentication with JWT tokens and refresh mechanism',
      status: 'in-progress',
      createdBy: 'John Manager',
      createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
      updatedAt: new Date(),
      steps: [
        {
          id: 's1',
          title: 'Design authentication flow',
          status: 'completed',
          completedAt: new Date(Date.now() - 1.5 * 24 * 60 * 60 * 1000),
          completedBy: 'Alice Developer',
        },
        {
          id: 's2',
          title: 'Implement JWT token generation',
          status: 'in-progress',
          completedBy: 'Bob Developer',
        },
        {
          id: 's3',
          title: 'Setup refresh token mechanism',
          status: 'pending',
        },
        {
          id: 's4',
          title: 'Write unit tests',
          status: 'pending',
        },
      ],
    },
    {
      id: '2',
      title: 'Database Migration',
      description: 'Migrate from MongoDB to PostgreSQL and optimize queries',
      status: 'pending',
      createdBy: 'John Manager',
      createdAt: new Date(Date.now() - 12 * 60 * 60 * 1000),
      updatedAt: new Date(),
      steps: [
        {
          id: 's1',
          title: 'Design database schema',
          status: 'pending',
        },
        {
          id: 's2',
          title: 'Create migration scripts',
          status: 'pending',
        },
        {
          id: 's3',
          title: 'Test data migration',
          status: 'pending',
        },
      ],
    },
  ]);

  const [currentRole, setCurrentRole] = useState<UserRole>('main-manager');
  const [currentUser, setCurrentUser] = useState<string>('John Manager');

  const addTodo = useCallback(
    (title: string, description: string, stepTitles: string[]) => {
      const newTodo: Todo = {
        id: `todo-${Date.now()}`,
        title,
        description,
        status: 'pending',
        createdBy: currentUser,
        createdAt: new Date(),
        updatedAt: new Date(),
        steps: stepTitles.map((stepTitle, index) => ({
          id: `step-${Date.now()}-${index}`,
          title: stepTitle,
          status: 'pending',
        })),
      };
      setTodos((prev) => [newTodo, ...prev]);
      return newTodo;
    },
    [currentUser]
  );

  const updateStepStatus = useCallback(
    (todoId: string, stepId: string, newStatus: StepStatus, completedBy?: string) => {
      setTodos((prev) =>
        prev.map((todo) => {
          if (todo.id === todoId) {
            const updatedSteps = todo.steps.map((step) => {
              if (step.id === stepId) {
                return {
                  ...step,
                  status: newStatus,
                  completedAt: newStatus === 'completed' ? new Date() : undefined,
                  completedBy: newStatus === 'completed' ? completedBy || currentUser : undefined,
                };
              }
              return step;
            });

            // Update todo status based on steps
            const allStepsCompleted = updatedSteps.every((s) => s.status === 'completed');
            const anyStepInProgress = updatedSteps.some((s) => s.status === 'in-progress');

            return {
              ...todo,
              steps: updatedSteps,
              status: allStepsCompleted ? 'completed' : anyStepInProgress ? 'in-progress' : 'pending',
              updatedAt: new Date(),
            };
          }
          return todo;
        })
      );
    },
    [currentUser]
  );

  const deleteTodo = useCallback((todoId: string) => {
    setTodos((prev) => prev.filter((todo) => todo.id !== todoId));
  }, []);

  return {
    todos,
    currentRole,
    setCurrentRole,
    currentUser,
    setCurrentUser,
    addTodo,
    updateStepStatus,
    deleteTodo,
  };
};
