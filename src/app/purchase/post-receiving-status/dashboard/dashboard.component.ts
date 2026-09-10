import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  isView = false;
  loading = false;
  orders;
  disc_amt=0;
  total=0;
  selectedOrder;
  remark = '';
  selectedMaterial=[];
  terms_condition=[];
  from_date='';
  to_date='';
  today='';
  status='';
  vendor_no='';
  departments;
  item = [];
  vendors;
  material_subtype='';
  results: any[] = [];
  filteredResults: any[] = [];
  paginatedResults: any[] = [];
  searchText = '';
  constructor(private service:DataAccessService,private datePipe:DatePipe, private router: Router) { 
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
 
    this.getReceivingLog();
  }

  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size
  readonly pageSizes = [10, 20, 50, 100];

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }
  
  get totalPages(): number {
    const len = this.filteredResults.length || 0;
    return Math.max(1, Math.ceil(len / this.pageSize));
  }

  onPageChange(page: number) {
    const p = Number(page);
    if (!p || p < 1) return;
    this.currentPage = Math.min(Math.max(1, p), this.totalPages);
    this.updatePagedResults();
  }
  
  onPageSizeChange(value: any) {
    this.pageSize = parseInt(String(value), 10);
    this.currentPage = 1;
    this.updatePagedResults();
  }

  onSearchChange(value: string) {
    this.searchText = (value || '').toLowerCase().trim();
    this.currentPage = 1;
    this.applyFilters();
  }

  applyFilters(): void {
    const q = this.searchText;
    if (!q) {
      this.filteredResults = [...this.results];
    } else {
      this.filteredResults = this.results.filter((row: any) => {
        const fields = [
          row?.material_type,
          row?.material_name,
          row?.material_code,
          row?.po_no,
          row?.po_status,
          row?.challan_status,
          row?.receiving,
          row?.weighing,
          row?.sample_status,
          row?.testing_status,
        ];
        return fields.some((f) => String(f || '').toLowerCase().includes(q));
      });
    }
    this.updatePagedResults();
  }

  updatePagedResults(): void {
    const start = (this.currentPage - 1) * this.pageSize;
    const end = start + this.pageSize;
    this.paginatedResults = this.filteredResults.slice(start, end);
  }

  goPrev(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.updatePagedResults();
    }
  }

  goNext(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
      this.updatePagedResults();
    }
  }

  statusClass(value: any): string {
    const v = String(value || '').toLowerCase();
    if (!v || v === '-') return 'status-na';
    if (v.includes('complete') || v.includes('done') || v.includes('approved') || v.includes('yes')) return 'status-ok';
    if (v.includes('pending') || v.includes('in process') || v.includes('progress')) return 'status-warn';
    if (v.includes('reject') || v.includes('cancel') || v.includes('fail')) return 'status-bad';
    return 'status-na';
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//

  getReceivingLog() {
    this.loading = true;
    this.service.get('store/raw.php?type=getpostmaterialStatus').subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
      this.applyFilters();
      this.loading = false;
    }, () => {
      this.results = [];
      this.filteredResults = [];
      this.paginatedResults = [];
      this.loading = false;
    });
  }

  
  download(){
    this.service.open('store/raw.php?type=downloadPostReceivingStatus');
  }
 
 
 

}
