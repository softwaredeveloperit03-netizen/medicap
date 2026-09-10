import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {

  isView = false;
  results;
  sections;
  racks;
  section = '';
  rack_no = '';

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingMaterials();
    this.getSections();
  }

  getPendingMaterials(){
    this.service.get('store/location.php?type=getPendingMaterials').subscribe(response => {
      this.results = response;
    });
  }


  getSections() {
    this.service.get('store/location.php?type=getSectionRacks').subscribe(response => {
      this.sections = response;
    });
  }

  allocate(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  getRacks(index) {
    index = index - 1;
    if (index !== -1) {
      this.racks = this.sections[index].racks;
    } else {
      this.racks = [];
    }
  }

  submitRacks(data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = this.selectedResult;
    temp['section'] = this.section;
    temp['rack_no'] = this.rack_no;
    this.service.post('store/location.php?type=allocateRack',JSON.stringify(temp)).subscribe(response => {
      if(response['status']  == 'success'){
        alertify.success('Data Submitted Successfully!');
        this.isView = false;
        this.getPendingMaterials();
      }else{
        alertify.error('Failed an error occured,Please try again!')
      }
    });
  }

}
