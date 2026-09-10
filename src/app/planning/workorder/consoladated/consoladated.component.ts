import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-consoladated',
  templateUrl: './consoladated.component.html',
  styleUrls: ['./consoladated.component.css']
})
export class ConsoladatedComponent implements OnInit {
  pendingpo = [];
  selectresult = [];
  isView=false;
  selectresultproduct =[];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPOsLog();
    
  }

  getPOsLog(){
    this.service.get('planning/workorder.php?type=getPendingProducts').subscribe((response: any) =>{
      this.pendingpo =response;
    });
  }

  
  view(index){
    this.selectresult = this.pendingpo[index];
    this.isView = true;
  }

  savework() {
    this.service.post('marketing/workorder.php?type=savePOWorkOrder', JSON.stringify(this.selectresult)).subscribe(response =>{
      if (response['status'] == 'success') {
        alertify.success('Record Inserted successfully');
        this.getPOsLog();
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
