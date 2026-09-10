import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  equipments;
  operators;
  selectedResult=[];
  selectedData=[];
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getEquipments();
    this.getOperator();
  }
  getEquipments(){
    this.service.get('equipments.php?type=getEquipments').subscribe(response => {
      const rows = Array.isArray(response) ? response : [];
      this.equipments = this.groupEquipments(rows);
    });
  }

  groupEquipments(rows: any[]): any[] {
    if (!rows.length) {
      return [];
    }
    if (rows[0] && Array.isArray(rows[0].equipments)) {
      return rows;
    }
    const grouped: { [name: string]: any } = {};
    for (const eq of rows) {
      const name = eq.equipment_name || '';
      if (!name) {
        continue;
      }
      if (!grouped[name]) {
        grouped[name] = { equipment_name: name, equipments: [] };
      }
      grouped[name].equipments.push({
        ...eq,
        clean: eq.clean || 'yes'
      });
    }
    return Object.keys(grouped).map(key => grouped[key]);
  }
  getOperator(){
    this.service.get('common.php?type=getOperatorsWorkerList').subscribe({
      next: (response: any) => {
        const rows = Array.isArray(response) ? response : [];
        if (rows.length) {
          this.operators = rows;
        } else {
          this.loadOperatorsFallback();
        }
      },
      error: () => this.loadOperatorsFallback()
    });
  }

  private loadOperatorsFallback() {
    this.service.get('employee.php?type=getQCPersons').subscribe({
      next: (response: any) => {
        const rows = Array.isArray(response) ? response : [];
        if (rows.length) {
          this.operators = rows;
        } else {
          this.loadAllEmployeesFallback();
        }
      },
      error: () => this.loadAllEmployeesFallback()
    });
  }

  private loadAllEmployeesFallback() {
    this.service.get('common.php?type=getemployee').subscribe({
      next: (response: any) => {
        this.operators = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.operators = [];
      }
    });
  }

  operatorValue(ope: any): string {
    if (!ope) {
      return '';
    }
    return ope.emp_id || ope.labour_no || '';
  }

  operatorLabel(ope: any): string {
    if (!ope) {
      return '';
    }
    const name = ((ope.firstname || '') + ' ' + (ope.lastname || '')).trim();
    return name || ope.labour_name || ope.emp_id || '';
  }
  getCode(index){
    index=index-1;
    this.selectedData = [];
    if(index !==-1){
      this.selectedResult=this.equipments[index];
    } else {
      this.selectedResult = [];
    }
  }
  getClean(index){
    index=index-1;
    if (index !== -1) {
      let equipments = this.selectedResult['equipments'];
      this.selectedData = equipments[index];
    }
  }
  saveUsage(data){
    if(!data.valid){
      alertify.error('all fields are required');
      return;
    }

    if (this.selectedData['clean'] == 'no') {
      alertify.error('Equipment not yet cleaned');
      return;
    }

    this.service.post('equipments.php?type=saveGeneralEquipmentUsages',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Equipment usage save successfuly');
        data.resetForm();
        this.router.navigate(['/qc/equipments/usages']);
      }else('some error occured!');
    });
  }
}
