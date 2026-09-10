import { Component, OnInit } from '@angular/core';
import {DataAccessService} from 'src/app/data-access.service'
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {
  results;
  results1;
  allocatedResults = [];
  allocatedResults1 = [];
  material_code='';
  material_name='';
  category='';
  grn_no='';
  selectedResult=[];
  isPerson = false;
  employees;
  constructor(private service :DataAccessService) { }

  ngOnInit(): void {
    this.getRetestCalender();
    this.getAllocatedAwaitingGrn();
    this.getQcPersons();
  }

  getAllocatedAwaitingGrn(){
    this.service.get('qc/retest.php?type=getAllocatedRetestsAwaitingGrn').subscribe((response: any) =>{
      this.allocatedResults1 = Array.isArray(response) ? response : [];
      this.filterAllocated();
    });
  }

  filterAllocated() {
    this.allocatedResults = [];
    for (let i = 0; i < (this.allocatedResults1 || []).length; i++) {
      let material = this.allocatedResults1[i];
      if(material['material_name'] == null || material['material_subtype'] == null){
        material['material_name']='';
        material['material_subtype']='';
      }
      if (material['material_code'].toUpperCase().includes(this.material_code.toUpperCase()) && material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())  && material['material_subtype'].toUpperCase().includes(this.category.toUpperCase())  && material['grn_no'].toUpperCase().includes(this.grn_no.toUpperCase())) {
        this.allocatedResults[this.allocatedResults.length] = material;
      }
    }
  }

  getRetestCalender(){
    this.service.get('qc/retest.php?type=getRetestCalender').subscribe(response =>{
      this.results1 = response;
     this.filterEquipment()
    });
  }

  allocate(index){
    this.selectedResult=this.results[index];
    this.isPerson = true;
  }

  filterEquipment() {
    this.results = [];
    for (let i = 0; i < this.results1.length; i++) {
      let material = this.results1[i];
      if(material['material_name'] == null || material['material_subtype'] == null){
        material['material_name']='';
        material['material_subtype']='';
      }
      if (material['material_code'].toUpperCase().includes(this.material_code.toUpperCase()) && material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())  && material['material_subtype'].toUpperCase().includes(this.category.toUpperCase())  && material['grn_no'].toUpperCase().includes(this.grn_no.toUpperCase())) {
        this.results[this.results.length] = material;
      }
    }
    this.filterAllocated();
  }

  getQcPersons() {
    this.service.get('qc/sampling.php?type=getQcPersons').subscribe(response => {
      this.employees = response;
    });
  }

  savePerson(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    temp['inword_no'] = this.selectedResult['inword_no'];
    temp['grn_no'] = this.selectedResult['grn_no'];
    temp['material_code'] = this.selectedResult['material_code'];
    temp['batch_no'] = this.selectedResult['batch_no'];
    temp['ar_no'] = this.selectedResult['ar_no'];
    temp['release_date'] = this.selectedResult['release_date'];
    temp['retest_date'] = this.selectedResult['retest_date'];
    temp['qty'] = this.selectedResult['qty'];
    temp['unit'] = this.selectedResult['unit'];
    temp['mfg_date'] = this.selectedResult['mfg_date'];
    temp['exp_date'] = this.selectedResult['exp_date'];
    this.service.post('qc/retest.php?type=allocateSamplingPerson', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Sampling person allocated. Open Retest → Sampling to continue.');
        this.isPerson = false;
        this.getRetestCalender();
        this.getAllocatedAwaitingGrn();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
