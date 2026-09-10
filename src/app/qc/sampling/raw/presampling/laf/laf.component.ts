import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-laf',
  templateUrl: './laf.component.html',
  styleUrls: ['./laf.component.css']
})
export class LafComponent implements OnInit {

  constructor(private service:DataAccessService,private router : Router) { }
isCode=false;
  ngOnInit(): void {
    this.getCheckPointData();
    this.getDetails();
    this.getEmployee();
    this.getLastBatchNoForlaf();
  }
 

   lastBatchData: any = {};
   cleaningAgentsUsed: any[] = [];

    getLastBatchNoForlaf(){
        this.service.get('qc/sampling/raw.php?type=getLastBatchNoForlaf').subscribe((response: any) => {
          this.lastBatchData = response || {};
          this.cleaningAgentsUsed = this.parseAgents(this.lastBatchData?.cleaningAgentsUsed);
        });
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

  
  checkPointData;
  getCheckPointData(){
    this.service.get('store/raw.php?type=getCheckPointByForm&module=Sampling&form=LAF Cleaning').subscribe(response => {
     this.checkPointData = response;
    });
  }


  lafEquipments;
  getDetails(){
    this.service.get('common.php?type=getLafForSamp&depart='+localStorage.getItem('department')+'&eq_type=LAF&eq_type2=RLAF').subscribe((response:any) => {
      this.lafEquipments = response;
    });
  }


  emps;
  getEmployee(){
    this.service.get('common.php?type=get_Eqgetemployee_byDeptipments&depart='+localStorage.getItem('department')).subscribe((response:any) => {
      this.emps = response;
    });
  }

  selectedEquipment = {};
  onchangeEquipment(e){
    const index = e.target.selectedIndex - 1;
    this.selectedEquipment = this.lafEquipments[index];
    this.isCode=true;
  }
 
  selectedResult = {};

   
   
    addRow(data) {

        let temp = data.value;

        temp['equipment_name'] = this.selectedEquipment['equipment_name'];
        temp['equipment_type'] = this.selectedEquipment['equipment_type'];
        temp['department'] = localStorage.getItem('department');

        const room = Number(temp['Roompressure']);
        const pre = Number(temp['Prepressure']);
        const mid = Number(temp['Mediatepressure']);
        const heap = Number(temp['Heappressure']);

        temp['status'] = 'PASS';

        if ( room < 5 || room > 15 || pre < 2 || pre > 6 || mid < 5 || mid > 10 || heap < 7 || heap > 20 ) {
          temp['status'] = 'FAIL';
        }else{
          temp['status'] = 'PASS';
        }

        temp['checkPointData'] = this.checkPointData;
      
        this.service.post('qc/sampling/cleaning.php?type=saveLAF_CLEAN_Activity',JSON.stringify(temp)).subscribe(response => {
          const result = JSON.parse(JSON.stringify(response));
          if (result.status === 'success') {
            data.reset();
            alertify.success(this.service.t('common.savedSuccess'));
          } else {
            alertify.error(this.service.t('common.errorOccurred'));
          }

        });
    }

}
