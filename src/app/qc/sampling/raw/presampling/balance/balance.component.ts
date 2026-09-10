import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-balance',
  templateUrl: './balance.component.html',
  styleUrls: ['./balance.component.css']
})
export class BalanceComponent implements OnInit {

 constructor(private service: DataAccessService, private router: Router) { }


  ngOnInit(): void {
    this.getPendingSamplingDataForBalanceCleaning();
    this.getQcPersons();
    this.getCheckPointData();
    this.getStoreBalance();
  }


  equipments;
  getStoreBalance(){
    this.service.get('common.php?type=get_BalanceEquipments&depart='+localStorage.getItem('department')).subscribe((response:any) => {
      this.equipments = response;
    });
  }



 
    results;
   material_type = 'Raw Material';
   getPendingSamplingDataForBalanceCleaning() {
     this.service.get('qc/sampling/raw.php?type=getPendingSamplingDataForBalanceCleaning&material_type=' + this.material_type).subscribe(response => {
       this.results = response;
     });
   }
 
    qcEmployee: any[] = [];
    getQcPersons() {
      this.service.get('hr/employee.php?type=getEmployeesbydept&department_name=' + encodeURIComponent('Quality Control')).subscribe((response: any) => {
        this.qcEmployee = Array.isArray(response) ? response : [];
      });
    }

    areaChecklistData: any[] = [];
    checkPointData: any[] = [];
    getCheckPointData(){
        this.service.get('master/checklist.php?type=getCheckPointByForm&module=Sampling&form=' + encodeURIComponent('Balance Cleaning')).subscribe((response: any) => {
          this.checkPointData = Array.isArray(response) ? response : [];
        });
    }

    loadAreaChecklistDisplay(object: any) {
      const saved = this.parseChecklist(object?.areaCleaningChecklist);
      if (saved.length > 0) {
        this.areaChecklistData = saved;
      }
      this.service.get('master/checklist.php?type=getCheckPointByForm&module=Sampling&form=' + encodeURIComponent('Area Cleaning')).subscribe((response: any) => {
        const masterRows = Array.isArray(response) ? response : [];
        if (saved.length > 0 && masterRows.length > 0) {
          this.areaChecklistData = masterRows.map((row, index) => {
            const match = saved.find((item) => item.id === row.id || item.check_point === row.check_point) || saved[index];
            return { ...row, check: match?.check ?? '' };
          });
        } else if (saved.length > 0) {
          this.areaChecklistData = saved;
        } else {
          this.areaChecklistData = masterRows;
        }
      });
    }

    private parseChecklist(raw: any): any[] {
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
 
 
    selectedResult;
    cleaningAgentsUsed: any[] = [];
    magnehelic1 = '';
    magnehelic2 = '';
    magnehelic3 = '';
 
    viewAreaClean(object){
      this.selectedResult = object;
      this.isView = true;
      this.magnehelic1 = '';
      this.magnehelic2 = '';
      this.magnehelic3 = '';
      this.cleaningAgentsUsed = [];
      this.areaChecklistData = [];
      this.loadAreaCleaningDetails(object);
      this.getCheckPointData();
      this.getLastBatchNoForweighing();
    }

    loadAreaCleaningDetails(object: any) {
      const fallback = object || {};
      this.applyAreaCleaningDetails(fallback);
      const id = fallback?.id;
      if (!id) {
        return;
      }
      this.service.get('qc/sampling/raw.php?type=getAreaCleaningDetailsForBalance&id=' + encodeURIComponent(id)).subscribe({
        next: (response: any) => {
          if (response && typeof response === 'object' && !Array.isArray(response)) {
            this.applyAreaCleaningDetails({ ...fallback, ...response });
          }
        },
      });
    }

    private applyAreaCleaningDetails(row: any) {
      this.cleaningAgentsUsed = this.parseAgents(row?.cleaningAgentsUsed);
      this.loadAreaChecklistDisplay(row);
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
 

    lastBatchData = {};
    getLastBatchNoForweighing(){
        this.service.get('qc/sampling/raw.php?type=getLastBatchNoForweighing').subscribe(response => {
          this.lastBatchData = response;
        });
    }

   isView = false;

  saveBalanceCleaningRecord(data){

    if(!data.valid){
      alertify.error('Please fill all required fields');
      return;
    }
    if (!this.magnehelic1 || !this.magnehelic2 || !this.magnehelic3) {
      alertify.error('Please enter Magnehelic Gauge Reading (#1, #2, #3)');
      return;
    }

    let temp = data.value;
    temp['balanceCleaningChecklist'] = this.checkPointData;
    temp['id'] = this.selectedResult['id'];
    temp['weighBalMagnehelic1'] = this.magnehelic1;
    temp['weighBalMagnehelic2'] = this.magnehelic2;
    temp['weighBalMagnehelic3'] = this.magnehelic3;

    this.service.post('qc/sampling.php?type=saveBalanceCleaningRecord', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.checkPointData=[];
        this.cleaningAgentsUsed = [];
        this.magnehelic1 = '';
        this.magnehelic2 = '';
        this.magnehelic3 = '';
        this.getCheckPointData();
        this.getPendingSamplingDataForBalanceCleaning();
        data.reset();
        this.isView = false;
        alertify.success( 'Saved Successfully');
      } else {
        alertify.error(result.status || this.service.t('common.errorOccurred'));
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
