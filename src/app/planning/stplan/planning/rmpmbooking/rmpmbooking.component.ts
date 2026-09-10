import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';
declare let alertify: any;

@Component({
  selector: 'app-rmpmbooking',
  templateUrl: './rmpmbooking.component.html',
  styleUrls: ['./rmpmbooking.component.css']
})
export class RmpmbookingComponent implements OnInit {

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute,
    private deptNav: DeptNavigationService
  ) { }

  loading: boolean = false;
  bookedStockData: any[] = [];
  filteredData: any[] = [];
  plant_id: string = '';
  
  // Filters
  materialTypeFilter: string = 'All'; // All, RM, PM
  workOrderFilter: string = '';
  materialCodeFilter: string = '';
  materialNameFilter: string = '';
  
  // Summary
  totalBookedRM: number = 0;
  totalBookedPM: number = 0;
  totalWorkOrders: number = 0;

  ngOnInit(): void {
    this.plant_id = this.service.getPlantConfigFields("plant_id");
    this.getBookedStock();
  }

  // Get booked stock data
  getBookedStock() {
    this.loading = true;
    this.service.get(`bmr/line_booking.php?type=getBookedStock&plant_id=${this.plant_id}`).subscribe(
      (response: any) => {
        this.bookedStockData = response || [];
        this.filteredData = [...this.bookedStockData];
        this.calculateSummary();
        this.loading = false;
      },
      (error) => {
        console.error('Error loading booked stock:', error);
        alertify.error('Error loading booked stock data');
        this.loading = false;
      }
    );
  }

  // Calculate summary totals
  calculateSummary() {
    this.totalBookedRM = 0;
    this.totalBookedPM = 0;
    const uniqueWorkOrders = new Set<string>();
    
    this.filteredData.forEach((item: any) => {
      if (item.material_type === 'Raw Material' || item.mat_type === 'RM') {
        this.totalBookedRM += parseFloat(item.booked_qty || item.required_qty || 0);
      } else if (item.material_type === 'Packing Material' || item.mat_type === 'PM') {
        this.totalBookedPM += parseFloat(item.booked_qty || item.required_qty || 0);
      }
      if (item.workorder_no) {
        uniqueWorkOrders.add(item.workorder_no);
      }
    });
    
    this.totalWorkOrders = uniqueWorkOrders.size;
  }

  // Apply filters
  applyFilters() {
    this.filteredData = this.bookedStockData.filter((item: any) => {
      // Material type filter
      if (this.materialTypeFilter !== 'All') {
        const itemType = item.material_type || item.mat_type || '';
        if (this.materialTypeFilter === 'RM' && !itemType.includes('Raw Material') && item.mat_type !== 'RM') {
          return false;
        }
        if (this.materialTypeFilter === 'PM' && !itemType.includes('Packing Material') && item.mat_type !== 'PM') {
          return false;
        }
      }
      
      // Work order filter
      if (this.workOrderFilter && !item.workorder_no?.toLowerCase().includes(this.workOrderFilter.toLowerCase())) {
        return false;
      }
      
      // Material code filter
      if (this.materialCodeFilter && !item.material_code?.toLowerCase().includes(this.materialCodeFilter.toLowerCase())) {
        return false;
      }
      
      // Material name filter
      if (this.materialNameFilter && !item.material_name?.toLowerCase().includes(this.materialNameFilter.toLowerCase())) {
        return false;
      }
      
      return true;
    });
    
    this.calculateSummary();
  }

  // Clear all filters
  clearFilters() {
    this.materialTypeFilter = 'All';
    this.workOrderFilter = '';
    this.materialCodeFilter = '';
    this.materialNameFilter = '';
    this.applyFilters();
  }

  // Export to Excel (placeholder)
  exportToExcel() {
    alertify.success('Export functionality will be implemented');
  }

  // Refresh data
  refresh() {
    this.getBookedStock();
  }

  // Go back to STP / Planning hub
  goBack() {
    this.deptNav.goBack(this.route, '/planning/stplan?returnUrl=%2Fplanning');
  }

}
