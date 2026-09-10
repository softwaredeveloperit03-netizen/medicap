import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-area',
  templateUrl: './area.component.html',
  styleUrls: ['./area.component.css']
})
export class AreaComponent implements OnInit {

 constructor(private service: DataAccessService, private router: Router) { }


  ngOnInit(): void {
    this.getPendingSamplingDataForAreaCleaning();
    this.getQcPersons();
    this.getCheckPointData();
    this.getCleaningAgents();
  }

 
    results;
   material_type = 'Raw Material';
   getPendingSamplingDataForAreaCleaning() {
     this.service.get('qc/sampling/raw.php?type=getPendingSamplingDataForAreaCleaning&material_type=' + this.material_type).subscribe(response => {
       this.results = response;
     });
   }
 
    qcEmployee: any[] = [];
    getQcPersons() {
      this.service.get('hr/employee.php?type=getEmployeesbydept&department_name=' + encodeURIComponent('Quality Control')).subscribe((response: any) => {
        this.qcEmployee = Array.isArray(response) ? response : [];
      });
    }

    checkPointData: any[] = [];
    getCheckPointData(){
        this.service.get('master/checklist.php?type=getCheckPointByForm&module=Sampling&form=' + encodeURIComponent('Area Cleaning')).subscribe((response: any) => {
          this.checkPointData = Array.isArray(response) ? response : [];
        });
    }

    lastBatchData = {};
    getLastBatchNoForArea(){
        this.service.get('qc/sampling/raw.php?type=getLastBatchNoForArea').subscribe(response => {
          this.lastBatchData = response;
        });
    }

    cleaningAgents: any[] = [];
    cleaningAgentsUsed: any[] = [];

    getCleaningAgents() {
      this.service.get('qc/sampling/sampling_room.php?type=getCleaningAgentsFromOthers').subscribe((response: any) => {
        this.cleaningAgents = this.normalizeCleaningAgents(response);
      });
    }

    private normalizeCleaningAgents(response: any): any[] {
      let list: any[] = [];
      if (Array.isArray(response)) {
        list = response;
      } else if (response && Array.isArray(response.data)) {
        list = response.data;
      }
      return list.map((a) => {
        const code = String(a?.material_code || '').trim();
        const lot = String(a?.batch_no || a?.medicap_lot_no || '').trim();
        return {
          ...a,
          batch_no: lot,
          medicap_lot_no: lot,
          option_key: a?.option_key || (lot ? `${code}|${lot}` : code),
        };
      });
    }

    addCleaningAgent() {
      this.cleaningAgentsUsed.push({
        option_key: '',
        material_code: '',
        material_name: '',
        unit: '',
        qty_used: '',
        batch_no: '',
        medicap_lot_no: '',
        exp_date: '',
      });
    }

    removeCleaningAgent(index: number) {
      this.cleaningAgentsUsed.splice(index, 1);
    }

    onCleaningAgentChange(index: number, optionKey: string) {
      const selected = this.cleaningAgents.find((a) => a.option_key === optionKey);
      if (!selected) {
        this.cleaningAgentsUsed[index].option_key = '';
        this.cleaningAgentsUsed[index].material_code = '';
        this.cleaningAgentsUsed[index].material_name = '';
        this.cleaningAgentsUsed[index].unit = '';
        this.cleaningAgentsUsed[index].batch_no = '';
        this.cleaningAgentsUsed[index].medicap_lot_no = '';
        this.cleaningAgentsUsed[index].exp_date = '';
        return;
      }
      const lot = selected.batch_no || selected.medicap_lot_no || '';
      this.cleaningAgentsUsed[index].option_key = selected.option_key;
      this.cleaningAgentsUsed[index].material_code = selected.material_code;
      this.cleaningAgentsUsed[index].material_name = selected.material_name;
      this.cleaningAgentsUsed[index].unit = selected.unit || '';
      this.cleaningAgentsUsed[index].batch_no = lot;
      this.cleaningAgentsUsed[index].medicap_lot_no = lot;
      this.cleaningAgentsUsed[index].exp_date = selected.exp_date || '';
    }
 
 
    selectedResult;
 
    viewAreaClean(object){
      this.selectedResult = object;
      this.isView = true;
      this.cleaningAgentsUsed = [];
      this.addCleaningAgent();
      this.getLastBatchNoForArea();
      this.getCheckPointData();
    }
 

   isView = false;

  saveAreaCleaningRecord(data){

    if(!data.valid){
      alertify.error('Please fill all required fields');
      return;
    }

    const agents = (this.cleaningAgentsUsed || []).filter(
      (a) => (a.material_code || '').trim() !== '' || (a.qty_used || '').toString().trim() !== ''
    );
    if (agents.length === 0) {
      alertify.error('Please add at least one Cleaning Agent Used');
      return;
    }
    for (const a of agents) {
      if (!(a.material_code || '').trim() || !(a.qty_used || '').toString().trim()) {
        alertify.error('Please select Cleaning Agent and Qty Used for all rows');
        return;
      }
    }

    const checklist = Array.isArray(this.checkPointData) ? this.checkPointData : [];
    if (checklist.length > 0) {
      for (const row of checklist) {
        if (!(row?.check || '').toString().trim()) {
          alertify.error('Please complete all Area Cleaning Checklist remarks');
          return;
        }
      }
    }

    if (!this.selectedResult?.id) {
      alertify.error('Sampling record not selected');
      return;
    }

    const temp = {
      ...data.value,
      areaCleaningChecklist: checklist,
      cleaningAgentsUsed: agents,
      id: this.selectedResult.id,
    };

    this.service.postJson('qc/sampling.php?type=saveAreaCleaningRecord', JSON.stringify(temp)).subscribe({
      next: (response) => {
        const result = typeof response === 'string' ? JSON.parse(response) : response;
        if (result?.status === 'success') {
          this.checkPointData = [];
          this.cleaningAgentsUsed = [];
          this.getCheckPointData();
          this.getPendingSamplingDataForAreaCleaning();
          data.reset();
          this.isView = false;
          alertify.success('Saved Successfully');
        } else {
          alertify.error(result?.message || result?.status || this.service.t('common.errorOccurred'));
        }
      },
      error: () => {
        alertify.error(this.service.t('common.errorOccurred'));
      },
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
