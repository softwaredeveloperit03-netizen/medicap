import { Component, OnInit } from '@angular/core';
//import { FormBuilder } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-po',
  templateUrl: './po.component.html',
  styleUrls: ['./po.component.css']
})
export class PoComponent implements OnInit {
  pendingpo = [];
  selectresult = [];
  isView=false;
  selectresultproduct =[];

  transporters;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPOsLog();
    this.getTransporters();
  }

  getPOsLog(){
    this.service.get('marketing/workorder.php?type=getPendingPos').subscribe((response: any) =>{
      this.pendingpo =response;
    });
  }

  getTransporters() {
    this.service.get('marketing/workorder.php?type=getTransporters').subscribe(response => {
      this.transporters = response;
    });
  }

  view(index){
    this.selectresult = this.pendingpo[index];
    this.isView = true;
  }

  savework(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('marketing/workorder.php?type=savePOWorkOrder', JSON.stringify(this.selectresult)).subscribe(response =>{
      if (response['status'] == 'success') {
        alert('Record Inserted successfully');
        this.getPOsLog();
        this.isView = false;
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  
}
