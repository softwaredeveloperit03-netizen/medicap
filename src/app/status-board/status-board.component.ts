import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http'; // Add this import

@Component({
  selector: 'app-status-board',
  templateUrl: './status-board.component.html',
  styleUrls: ['./status-board.component.css']
})
export class StatusBoardComponent implements OnInit {
  
  // Dashboard Data
  dashboardData: any = null;
  
  // Filtering
  searchTerm: string = '';
  selectedModule: string = '';
  selectedStatus: string = '';
  modules: any[] = [];    

  // Data storage
  allPlans: any[] = [];
  
  // Loading state
  isLoading: boolean = false;
  
  // Toast Alert Properties
  showToast: boolean = false;
  toastMessage: string = '';
  toastType: 'info' | 'warning' | 'success' | 'danger' = 'info';
  toastClosed: boolean = true;

  // Replace DataAccessService with HttpClient
  constructor(private http: HttpClient) {} // Changed from dataAccess to http

  ngOnInit() {
    this.loadNootanDashboardData();
  }

  // Toast Alert Methods
  showAlert(message: string, type: 'info' | 'warning' | 'success' | 'danger' = 'info') {
    this.toastMessage = message;
    this.toastType = type;
    this.showToast = true;
    this.toastClosed = false;

    setTimeout(() => {
      this.showToast = false;
    }, 3000);
  }

  // Load data for Nootan Pharmaceuticals (client_id=146) - Using direct API URL
  loadNootanDashboardData() {
    this.isLoading = true;
    
    const apiUrl = `https://aurenyxgmp.com/admin/statusBoard/plan_api.php?action=getPlanscyclone&client_id=159`;

    // Using HttpClient directly instead of dataAccess service
    this.http.get(apiUrl)
      .subscribe({
        next: (response: any) => {
          this.isLoading = false;
          console.log('API Response for Nootan Pharmaceuticals:', response);
          
          if (response.success && response.data) {
            console.log('Nootan Pharmaceuticals plans data:', response.data);
            this.allPlans = response.data;
            this.processDashboardData(this.allPlans, 'Nootan Pharmaceuticals');
          } else {
            this.showAlert('No data found for Nootan Pharmaceuticals', 'warning');
            this.dashboardData = this.createEmptyDashboardData();
          }
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Error loading dashboard data:', error);
          this.showAlert('Error loading dashboard data', 'danger');
          this.dashboardData = this.createEmptyDashboardData();
        }
      });
  }

  createEmptyDashboardData() {
    return {
      totalProjects: 0,
      plans: [],
      developmentStats: { completed: 0, inProgress: 0, notStarted: 0 },
      testingStats: { completed: 0, inProgress: 0, notStarted: 0 },
      trainingStats: { completed: 0, inProgress: 0, notStarted: 0 },
      trialStats: { completed: 0, inProgress: 0, notStarted: 0 },
      implementationStats: { completed: 0, inProgress: 0, notStarted: 0 }
    };
  }

  processDashboardData(plansData: any[], dataSource: string = 'Unknown') {
    console.log(`=== Processing Dashboard Data (Source: ${dataSource}) ===`);
    console.log('Total records to process:', plansData?.length);
    
    if (!plansData || !Array.isArray(plansData)) {
      plansData = [];
    }

    // Log sample data to see actual field names
    if (plansData.length > 0) {
      console.log('Sample plan data structure:', plansData[0]);
      console.log('Available fields in first plan:', Object.keys(plansData[0]));
    }

    // Initialize dashboard data structure
    this.dashboardData = {
      totalProjects: plansData.length,
      plans: this.mapPlanFields(plansData),
      developmentStats: { completed: 0, inProgress: 0, notStarted: 0 },
      testingStats: { completed: 0, inProgress: 0, notStarted: 0 },
      trainingStats: { completed: 0, inProgress: 0, notStarted: 0 },
      trialStats: { completed: 0, inProgress: 0, notStarted: 0 },
      implementationStats: { completed: 0, inProgress: 0, notStarted: 0 }
    };

    // Calculate statistics
    this.dashboardData.plans.forEach((plan: any) => {
      this.updateStats('development', plan.development_status || 'not-started');
      this.updateStats('testing', plan.testing_status || 'not-started');
      this.updateStats('training', plan.training_status || 'not-started');
      this.updateStats('trial', plan.trial_status || 'not-started');
      this.updateStats('implementation', plan.implementation_status || 'not-started');
    });

    // Extract filter options from current dataset
    this.modules = [...new Set(this.dashboardData.plans.map((p: any) => p.module).filter((m: any) => m))];

    console.log('Processed dashboard data summary:', {
      totalProjects: this.dashboardData.totalProjects,
      developmentCompleted: this.dashboardData.developmentStats.completed,
      testingCompleted: this.dashboardData.testingStats.completed,
      trainingCompleted: this.dashboardData.trainingStats.completed,
      trialCompleted: this.dashboardData.trialStats.completed,
      implementationCompleted: this.dashboardData.implementationStats.completed,
      availableModules: this.modules
    });
    
    // Log first plan to verify field mapping
    if (this.dashboardData.plans.length > 0) {
      console.log('First plan after mapping:', this.dashboardData.plans[0]);
    }
    
    console.log('=== End Processing ===');
  }

  mapPlanFields(plans: any[]): any[] {
    return plans.map(plan => {
      // Check for all possible field names for training deviation
      const trainingDeviationDays = 
        plan.training_deviation_days !== undefined ? plan.training_deviation_days :
        plan.training_deviation !== undefined ? plan.training_deviation :
        plan.training_deviation_days !== undefined ? plan.training_deviation_days :
        null;

      return {
        // Basic information
        id: plan.id,
        client_id: plan.client_id,
        client_name: plan.client_name,
        phase: plan.phase,
        department: plan.department,
        module: plan.module,
        sub_module: plan.sub_module,
        form_name: plan.form_name,
        
        // Development fields
        current_projected_date: plan.current_projected_date,
        developer_name: plan.developer_name,
        development_status: plan.development_status,
        development_completed_on: plan.completed_on,
        deviation_days: plan.deviation_days,
        development_remark: plan.development_remark,
        
        // Testing fields
        current_testing_projected_date: plan.current_testing_projected_date,
        tester_name: plan.tester_name,
        testing_status: plan.testing_status,
        testing_completed_on: plan.test_completed_on,
        testing_deviation_days: plan.testing_deviation_days,
        testing_remark: plan.testing_remark,
        
        // Training fields
        current_training_projected_date: plan.current_training_projected_date,
        trainer_name: plan.trainer_name,
        training_status: plan.training_status,
        training_completed_on: plan.training_completed_on,
        training_deviation_days: trainingDeviationDays,
        training_remark: plan.training_remark,
        
        // Trial fields
        current_trial_projected_date: plan.current_trial_projected_date,
        trialer_name: plan.trialer_name,
        trial_status: plan.trial_status,
        trial_completed_on: plan.trial_completed_on,
        trial_deviation_days: plan.trial_deviation_days,
        trial_remark: plan.trial_remark,
        
        // Implementation fields
        current_implementation_projected_date: plan.current_implementation_projected_date,
        implementer_name: plan.implementer_name,
        implementation_status: plan.implementation_status,
        implementation_completed_on: plan.implementation_completed_on,
        implementation_deviation_days: plan.implementation_deviation_days,
        implementation_remark: plan.implementation_remark,
        
        // Keep original fields
        ...plan
      };
    });
  }

  updateStats(category: string, status: string) {
    const stats = this.dashboardData[`${category}Stats`];
    
    if (status === 'completed' || status === 'live' || status === 'passed' || status === 'successful') {
      stats.completed++;
    } else if (status === 'in-progress' || status === 'started' || status === 'scheduled' || 
               status === 'planned' || status === 'under-review') {
      stats.inProgress++;
    } else {
      stats.notStarted++;
    }
  }

  refreshData() {
    console.log('Refreshing Nootan Pharmaceuticals data...');
    this.loadNootanDashboardData();
  }

  filterPlans(): any[] {
    if (!this.dashboardData?.plans) {
      console.log('No plans data available for filtering');
      return [];
    }

    console.log('=== Applying Filters ===');
    console.log('Search Term:', this.searchTerm);
    console.log('Selected Module:', this.selectedModule);
    console.log('Selected Status:', this.selectedStatus);
    console.log('Total plans before filtering:', this.dashboardData.plans.length);

    let dataToFilter = this.dashboardData.plans;

    // Apply filters
    const filtered = dataToFilter.filter((plan: any) => {
      const matchesSearch = !this.searchTerm || 
        (plan.form_name?.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
         plan.sub_module?.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
         plan.client_name?.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
         plan.module?.toLowerCase().includes(this.searchTerm.toLowerCase()));

      const matchesModule = !this.selectedModule || plan.module === this.selectedModule;
      
      let matchesStatus = true;
      if (this.selectedStatus) {
        matchesStatus = 
          plan.development_status === this.selectedStatus ||
          plan.testing_status === this.selectedStatus ||
          plan.training_status === this.selectedStatus ||
          plan.trial_status === this.selectedStatus ||
          plan.implementation_status === this.selectedStatus;
      }

      return matchesSearch && matchesModule && matchesStatus;
    });

    console.log(`Final filtering results: ${filtered.length} out of ${dataToFilter.length} plans match criteria`);
    
    return filtered;
  }

  // Status display methods
  getDevelopmentStatusClass(status: string): string {
    return this.getStatusClass(status);
  }

  getTestingStatusClass(status: string): string {
    return this.getStatusClass(status);
  }

  getTrainingStatusClass(status: string): string {
    return this.getStatusClass(status);
  }

  getTrialStatusClass(status: string): string {
    return this.getStatusClass(status);
  }

  getImplementationStatusClass(status: string): string {
    return this.getStatusClass(status);
  }

  getDevelopmentStatusDisplayText(status: string): string {
    return this.getStatusDisplayText(status);
  }

  getTestingStatusDisplayText(status: string): string {
    return this.getStatusDisplayText(status);
  }

  getTrainingStatusDisplayText(status: string): string {
    return this.getStatusDisplayText(status);
  }

  getTrialStatusDisplayText(status: string): string {
    return this.getStatusDisplayText(status);
  }

  getImplementationStatusDisplayText(status: string): string {
    return this.getStatusDisplayText(status);
  }

  getStatusClass(status: string): string {
    if (!status) return 'label-light-blue';
    
    const statusMap: { [key: string]: string } = {
      'not-started': 'label-light-blue',
      'started': 'label-info',
      'in-progress': 'label-info',
      'planned': 'label-blue',
      'scheduled': 'label-blue',
      'completed': 'label-success',
      'live': 'label-success',
      'passed': 'label-success',
      'failed': 'label-danger',
      'successful': 'label-success',
      'cancelled': 'label-danger',
      'on-hold': 'label-warning',
      'under-review': 'label-warning'
    };

    return statusMap[status] || 'label-light-blue';
  }

  getStatusDisplayText(status: string): string {
    if (!status) return 'Not Started';
    
    const statusMap: { [key: string]: string } = {
      'not-started': 'Not Started',
      'started': 'Started',
      'in-progress': 'In Progress',
      'planned': 'Planned',
      'scheduled': 'Scheduled',
      'completed': 'Completed',
      'live': 'Live',
      'passed': 'Passed',
      'failed': 'Failed',
      'successful': 'Successful',
      'cancelled': 'Cancelled',
      'on-hold': 'On Hold',
      'under-review': 'Under Review'
    };

    return statusMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
  }

  formatDate(dateString: string): string {
    if (!dateString) return '';
    
    try {
      const date = new Date(dateString);
      if (isNaN(date.getTime())) {
        return dateString;
      }
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    } catch (error) {
      return dateString;
    }
  }

  exportToExcel() {
    const plans = this.filterPlans();
    if (!plans.length) {
      this.showAlert('No data to export', 'warning');
      return;
    }

    const headers = [
      'Client', 'Phase', 'Department', 'Module', 'Sub Module', 'Form Name',
      'Development Status', 'Development Completed On', 'Developer',
      'Testing Status', 'Test Completed On', 'Tester',
      'Training Status', 'Training Completed On', 'Trainer',
      'Trial Status', 'Trial Completed On', 'Trialer',
      'Implementation Status', 'Implementation Completed On', 'Implementer'
    ];

    const csvData = plans.map(plan => [
      plan.client_name || '',
      plan.phase || '',
      plan.department || '',
      plan.module || '',
      plan.sub_module || '',
      plan.form_name || '',
      this.getStatusDisplayText(plan.development_status),
      plan.development_completed_on || '',
      plan.developer_name || '',
      this.getStatusDisplayText(plan.testing_status),
      plan.testing_completed_on || '',
      plan.tester_name || '',
      this.getStatusDisplayText(plan.training_status),
      plan.training_completed_on || '',
      plan.trainer_name || '',
      this.getStatusDisplayText(plan.trial_status),
      plan.trial_completed_on || '',
      plan.trialer_name || '',
      this.getStatusDisplayText(plan.implementation_status),
      plan.implementation_completed_on || '',
      plan.implementer_name || ''
    ]);

    const csvContent = [headers, ...csvData]
      .map(row => row.map(field => `"${field}"`).join(','))
      .join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `nootan-status-dashboard-${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    window.URL.revokeObjectURL(url);

    this.showAlert('Data exported successfully', 'success');
  }

  // Add computed property for template
  get filteredPlans(): any[] {
    return this.filterPlans();
  }
}