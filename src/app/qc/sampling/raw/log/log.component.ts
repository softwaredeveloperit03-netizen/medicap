import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  retestMode = false;
  listCloseRoute = '/qc/sampling/raw';
 
  constructor(private service: DataAccessService, private route: ActivatedRoute) {
    this.retestMode = !!this.route.snapshot.data['retestMode'];
    this.listCloseRoute = this.retestMode ? '/qc/sampling/retest' : '/qc/sampling/raw';
    if (this.retestMode) {
      this.material_type = '';
    }
  }

  get listTitle(): string {
    return this.retestMode ? 'Retest Sampling Log' : `${this.material_type} Sampling Log`;
  }

  get checklistTitle(): string {
    return this.retestMode ? 'RETEST SAMPLING & INSPECTION CHECKLIST' : `${this.material_type} SAMPLING & INSPECTION CHECKLIST`;
  }

  readonly testingAllocationRoute = '/qc/testing-rds/raw/allocation';

  ngOnInit() {
    this.getSamplings();
  }
  

  results;
  material_type = 'Raw Material';
  showAuditTrail = false;
  auditLoading = false;
  auditResults: any[] = [];
  from_date = '';
  to_date = '';
  auditSearch = '';
  pageSizeOptions: number[] = [10, 20, 30, 50, 100];
  pageSize = 20;
  currentPage = 1;

  initAuditDates(): void {
    if (this.from_date && this.to_date) {
      return;
    }
    const now = new Date();
    this.to_date = now.toISOString().slice(0, 10);
    const fromObj = new Date();
    fromObj.setDate(fromObj.getDate() - 30);
    this.from_date = fromObj.toISOString().slice(0, 10);
  }

  openAuditTrail(): void {
    this.showAuditTrail = true;
    this.initAuditDates();
    this.getSamplingAuditTrail();
  }

  closeAuditTrail(): void {
    this.showAuditTrail = false;
  }

  getSamplingAuditTrail(): void {
    this.auditLoading = true;
    this.service.get('qc/sampling.php?type=getSamplingAuditTrail&from_date=' + encodeURIComponent(this.from_date) + '&to_date=' + encodeURIComponent(this.to_date))
      .subscribe((response: any) => {
        this.auditResults = Array.isArray(response) ? response : [];
        this.auditLoading = false;
        this.currentPage = 1;
      }, () => {
        this.auditResults = [];
        this.auditLoading = false;
      });
  }

  onPageSizeChange(value: any): void {
    const size = Number(value);
    this.pageSize = Number.isFinite(size) && size > 0 ? size : 20;
    this.currentPage = 1;
  }

  getSrNo(index: number): number {
    return (this.currentPage - 1) * this.pageSize + index + 1;
  }

  get filteredAuditRows(): any[] {
    if (!this.auditSearch || this.auditSearch.trim() === '') {
      return this.auditResults || [];
    }
    const q = this.auditSearch.toLowerCase().trim();
    return (this.auditResults || []).filter((row: any) =>
      Object.values(row || {}).some((v) => v != null && String(v).toLowerCase().includes(q))
    );
  }
  getSamplings() {
    const mt = this.material_type ? `&material_type=${encodeURIComponent(this.material_type)}` : '';
    this.service.get(`qc/sampling.php?type=getSamplings${mt}${this.retestMode ? '&sampling_scope=retest' : ''}`).subscribe(response => {
      this.results = response;
    });
  }

 

  downloadPDF(){
      this.service.open('qc/sampling/raw.php?type=downloadSamplingsRecord&id=' + this.selectedSampling['id']);
  }

  downloadSamplingLog(){
      const mt = encodeURIComponent(this.material_type || '');
      const scope = this.retestMode ? '&sampling_scope=retest' : '';
      this.service.open('qc/sampling/raw.php?type=downloadSamplingLog&material_type=' + mt + scope);
  }

  printSampledByQcLabel(data) {
    if (!data || !data.id) {
      return;
    }
    this.service.open('qc/sampling/raw.php?type=printSampledByQcLabel&id=' + data.id);
  }




  selectedSampling = {};
  areaCleaningAgents: any[] = [];
  view(data) {
    this.selectedSampling = data;
    this.areaCleaningAgents = this.parseAgents(data?.['cleaningAgentsUsed']);
    this.isView = true;
  }

  private parseAgents(raw: any): any[] {
    if (!raw) {
      return [];
    }
    if (Array.isArray(raw)) {
      return raw;
    }
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        return [];
      }
    }
    return [];
  }
 
   searchQuery;
 
   get filteredMaterials(): any[] {
     if (!this.searchQuery || this.searchQuery.trim() === '') {
       return this.results; // If search query is empty or whitespace, return all materials
     }
 
     const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
     return this.results.filter((material) => {
       // Check if any field of the material contains the search query
       return Object.entries(material).some(([key, value]) => {
         if (key === 'entry_date') {
           // Convert the value to a Date object if it's not already
           const dateValue = typeof value === 'string' ? new Date(value) : value;
           // Check if the date value is valid and includes the search query
           return (
             dateValue instanceof Date &&
             dateValue.toISOString().slice(0, 10).includes(query)
           );
         } else {
           // Convert field value to lowercase and check if it includes the search query
           return value && value.toString().toLowerCase().includes(query);
         }
       });
     });
   }



}
