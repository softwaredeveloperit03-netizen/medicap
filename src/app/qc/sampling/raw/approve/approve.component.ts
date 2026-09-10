import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css']
})
export class ApproveComponent implements OnInit {
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
    return this.retestMode ? 'Retest Sampling For Approval' : `${this.material_type} Sampling For Approval`;
  }

  get checklistTitle(): string {
    return this.retestMode ? 'RETEST SAMPLING & INSPECTION CHECKLIST' : `${this.material_type} SAMPLING & INSPECTION CHECKLIST`;
  }

  ngOnInit() {
    this.getCheckedSamplings();
  }
  

  results;
  material_type = 'Raw Material';
  getCheckedSamplings() {
    const mt = this.material_type ? `&material_type=${encodeURIComponent(this.material_type)}` : '';
    this.service.get(`qc/sampling.php?type=getCheckedSamplings${mt}${this.retestMode ? '&sampling_scope=retest' : ''}`).subscribe(response => {
      this.results = response;
    });
  }

 

  selectedSampling = {};
  view(data) {
    this.selectedSampling = data;
    this.isView = true;
  }

  approvalRemark = '';
  updateSampling(data,status) {

    if(this.approvalRemark == ''){
      alertify.error("Please Add Remark....");
      return;
    }

    let temp = data.value;
    temp['id'] = this.selectedSampling['id'];
    temp['specification_no'] = this.selectedSampling['specification_no'];
    temp['sampling_no'] = this.selectedSampling['sampling_no']; 
    temp['grn_no'] = this.selectedSampling['grn_no'];
    temp['batch_no'] = this.selectedSampling['batch_no'];
    temp['ar_no'] = this.selectedSampling['ar_no'];
    temp['material_code'] = this.selectedSampling['material_code'];
    temp['convertedQtyToBaseUnit'] = this.selectedSampling['convertedQtyToBaseUnit'];
    temp['baseUnit'] = this.selectedSampling['baseUnit'];
    temp['material_type'] = this.selectedSampling['material_type'];
    temp['status'] = status;
 
    this.service.post('qc/sampling.php?type=updateCheckedSampling', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.approvalRemark = '';
        this.getCheckedSamplings();
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
