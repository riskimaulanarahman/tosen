# Diagram Arsitektur Pivot Table Absensi

## 1. High-Level Architecture

```mermaid
graph TB
    subgraph "Frontend Layer"
        A[PivotTable.vue] --> B[Filter Component]
        A --> C[Navigation Component]
        A --> D[Export Component]
        A --> E[Legend Component]
    end

    subgraph "Backend Layer"
        F[ReportController] --> G[PivotTableService]
        G --> H[Attendance Model]
        G --> I[User Model]
        G --> J[Outlet Model]
    end

    subgraph "Data Layer"
        K[MySQL Database]
        L[Cache Layer]
    end

    A --> F
    H --> K
    I --> K
    J --> K
    G --> L
```

## 2. Data Flow Diagram

```mermaid
sequenceDiagram
    participant User
    participant PivotTable as PivotTable.vue
    participant Controller as ReportController
    participant Service as PivotTableService
    participant DB as Database
    participant Cache as Cache Layer

    User->>PivotTable: Request pivot table
    PivotTable->>Controller: GET /reports/pivot
    Controller->>Service: generatePivotTable()

    Service->>Cache: Check cached data
    alt Cache exists
        Cache-->>Service: Return cached data
    else No cache
        Service->>DB: Query employees
        DB-->>Service: Employee data
        Service->>DB: Query attendances
        DB-->>Service: Attendance data
        Service->>Service: Transform to pivot format
        Service->>Cache: Store transformed data
    end

    Service-->>Controller: Pivot table data
    Controller-->>PivotTable: JSON response
    PivotTable-->>User: Render pivot table
```

## 3. Pivot Table Data Structure

```mermaid
erDiagram
    PIVOT_DATA {
        object employees
        object date_range
        object summary
    }

    EMPLOYEE {
        int id
        string name
        string email
        string outlet
        object attendances
    }

    ATTENDANCE {
        string status
        string check_in_time
        string check_out_time
        string work_duration
        int late_minutes
        int early_checkout_minutes
        int overtime_minutes
    }

    DATE_RANGE {
        string start
        string end
        array dates
        int total_days
    }

    SUMMARY {
        int total_employees
        object attendance_summary
    }

    PIVOT_DATA ||--o{ EMPLOYEE : contains
    EMPLOYEE ||--o{ ATTENDANCE : contains
    PIVOT_DATA ||--|| DATE_RANGE : contains
    PIVOT_DATA ||--|| SUMMARY : contains
```

## 4. Component Architecture

```mermaid
graph TD
    A[PivotTable.vue] --> B[Header Section]
    A --> C[Statistics Cards]
    A --> D[Filters & Navigation]
    A --> E[Status Legend]
    A --> F[Pivot Table]

    B --> B1[Title & Description]
    B --> B2[Export Button]

    C --> C1[Total Records]
    C --> C2[On Time Count]
    C --> C3[Late Count]
    C --> C4[Absent Count]
    C --> C5[Attendance Rate]

    D --> D1[Outlet Filter]
    D --> D2[Date Range Filter]
    D --> D3[Apply/Reset Buttons]
    D --> D4[Month Navigation]

    E --> E1[Status Icons]
    E --> E2[Color Codes]
    E --> E3[Status Labels]

    F --> F1[Fixed Employee Column]
    F --> F2[Scrollable Date Columns]
    F --> F3[Status Cells]
    F --> F4[Tooltips]
```

## 5. Service Layer Flow

```mermaid
flowchart TD
    A[generatePivotTable] --> B[Get Date Range]
    B --> C[Generate Date Array]
    C --> D[Query Employees]
    D --> E[Apply Outlet Filter]
    E --> F[Query Attendances]
    F --> G[Apply Date Range Filter]
    G --> H[Group by Employee]
    H --> I[Create Attendance Map]
    I --> J[Handle Missing Dates]
    J --> K[Calculate Summary Stats]
    K --> L[Return Structured Data]

    M[getStatusConfig] --> N[Define Status Colors]
    N --> O[Define Status Icons]
    O --> P[Define Status Labels]
    P --> Q[Return Config Object]

    R[exportToCsv] --> S[Generate CSV Headers]
    S --> T[Process Employee Rows]
    T --> U[Format Status Values]
    U --> V[Return CSV Data]
```

## 6. Database Query Optimization

```mermaid
graph LR
    A[Query 1: Employees] --> B[Index: role + outlet_id]
    C[Query 2: Attendances] --> D[Index: user_id + check_in_date]
    E[Query 3: Outlets] --> F[Index: owner_id]

    G[Join Strategy] --> H[Eager Loading]
    H --> I[with relationships]
    I --> J[Single Query Execution]
```

## 7. Caching Strategy

```mermaid
graph TB
    A[Cache Key Generation] --> B[owner_id + date_range + outlet_id]
    B --> C[Check Cache]
    C --> D{Cache Hit?}
    D -->|Yes| E[Return Cached Data]
    D -->|No| F[Query Database]
    F --> G[Transform Data]
    G --> H[Store in Cache]
    H --> I[Set TTL: 5 minutes]
    I --> J[Return Data]
```

## 8. Frontend State Management

```mermaid
stateDiagram-v2
    [*] --> Loading
    Loading --> Loaded
    Loading --> Error
    Loaded --> Filtering
    Filtering --> Loaded
    Loaded --> Exporting
    Exporting --> Loaded
    Error --> Loading
    Loaded --> [*]
```

## 9. Responsive Design Breakpoints

```mermaid
graph LR
    A[Mobile: < 768px] --> A1[Stacked Layout]
    A1 --> A2[Horizontal Scroll]
    A2 --> A3[Simplified Stats]

    B[Tablet: 768-1024px] --> B1[Compact Table]
    B1 --> B2[Reduced Columns]
    B2 --> B3[Touch Controls]

    C[Desktop: > 1024px] --> C1[Full Table]
    C1 --> C2[All Columns Visible]
    C2 --> C3[Hover Effects]
```

## 10. Performance Optimization Flow

```mermaid
flowchart TD
    A[User Request] --> B[Check Data Size]
    B --> C{Date Range > 3 months?}
    C -->|Yes| D[Show Warning]
    C -->|No| E[Proceed]
    D --> F[Confirm Limitation]
    F --> E
    E --> G[Enable Loading State]
    G --> H[Fetch Data]
    H --> I[Process in Chunks]
    I --> J[Render Virtual Table]
    J --> K[Disable Loading State]
    K --> L[Enable Interactions]
```

## 11. Error Handling Flow

```mermaid
flowchart TD
    A[API Call] --> B{Response Success?}
    B -->|Yes| C[Process Data]
    B -->|No| D[Check Error Type]
    D --> E{Validation Error?}
    D --> F{Network Error?}
    D --> G{Server Error?}

    E -->|Yes| H[Show Field Errors]
    F -->|Yes| I[Show Network Message]
    G -->|Yes| J[Show Server Error]

    H --> K[Retry Option]
    I --> K
    J --> K
    K --> L[User Action]
    L --> M{Retry?}
    M -->|Yes| A
    M -->|No| N[Show Fallback UI]
```

## 12. Export Functionality

```mermaid
graph TB
    A[User Clicks Export] --> B[Collect Current Filters]
    B --> C[Generate Export Key]
    C --> D[Call Export Endpoint]
    D --> E[Service Processes Data]
    E --> F[Generate CSV Content]
    F --> G[Set Response Headers]
    G --> H[Trigger Download]
    H --> I[Browser Saves File]
```

## 13. Navigation Flow

```mermaid
stateDiagram-v2
    [*] --> CurrentMonth
    CurrentMonth --> PreviousMonth: Click Prev
    CurrentMonth --> NextMonth: Click Next
    PreviousMonth --> CurrentMonth: Click Next
    NextMonth --> CurrentMonth: Click Prev

    CurrentMonth --> CustomRange: Select Dates
    CustomRange --> CurrentMonth: Reset
    CustomRange --> PreviousMonth: Navigate
    CustomRange --> NextMonth: Navigate
```

## 14. Component Interaction Map

```mermaid
graph LR
    A[PivotTable.vue] --> B[Filter Component]
    A --> C[Navigation Component]
    A --> D[Export Component]
    A --> E[Legend Component]
    A --> F[Table Component]

    B --> G[Date Picker]
    B --> H[Outlet Select]

    C --> I[Month Buttons]
    C --> J[Quick Filters]

    D --> K[CSV Export]
    D --> L[Excel Export]

    E --> M[Status Items]
    E --> N[Color Swatches]

    F --> O[Fixed Columns]
    F --> P[Scrollable Area]
    F --> Q[Cell Tooltips]
```

## 15. Security Considerations

```mermaid
graph TB
    A[Authentication] --> B[User Logged In]
    B --> C[Authorization Check]
    C --> D{Owner Role?}
    D -->|Yes| E[Access Granted]
    D -->|No| F[Access Denied]

    E --> G[Data Scoping]
    G --> H[Owner's Data Only]
    H --> I[Outlet Filtering]
    I --> J[Valid Date Range]
    J --> K[Rate Limiting]
    K --> L[Output Sanitization]
```
