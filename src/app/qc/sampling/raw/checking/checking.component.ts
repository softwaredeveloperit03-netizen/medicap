import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
   
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
    return this.retestMode ? 'Retest Sampling For Checking' : `${this.material_type} Sampling For Checking`;
  }

  get checklistTitle(): string {
    return this.retestMode ? 'RETEST SAMPLING & INSPECTION CHECKLIST' : `${this.material_type} SAMPLING & INSPECTION CHECKLIST`;
  }

  ngOnInit() {
    this.getActiveSamplings();
  }
  

  results;
  material_type = 'Raw Material';
  getActiveSamplings() {
    const mt = this.material_type ? `&material_type=${encodeURIComponent(this.material_type)}` : '';
    this.service.get(`qc/sampling.php?type=getActiveSamplings${mt}${this.retestMode ? '&sampling_scope=retest' : ''}`).subscribe(response => {
      this.results = response;
    });
  }

 

  selectedSampling: Record<string, any> = {};
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

  /** Per-container qty if saved; otherwise header Sample Quantity (item 15). */
  containerSampleQty(cont: Record<string, any>): string {
    const unit = String(this.selectedSampling['sample_unit'] || '').trim();
    const qty =
      cont?.sample_qty ||
      cont?.total_qty ||
      this.selectedSampling['sample_qty'] ||
      this.selectedSampling['totalsample_qty'] ||
      '';
    if (!qty && qty !== 0) {
      return '-';
    }
    return unit ? String(qty) + ' ' + unit : String(qty);
  }

  checkingRemark = '';
  updateSampling(data,status) {

    if(this.checkingRemark == ''){
      alertify.error("Please Add Remark....");
      return;
    }

    let temp = data.value;
    temp['id'] = this.selectedSampling['id'];
    temp['status'] = status;
 
    this.service.post('qc/sampling.php?type=updateActiveSampling', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.checkingRemark = '';
        this.getActiveSamplings();
      }else{
        alertify.error("some error Ocuured");
      }
    });
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
