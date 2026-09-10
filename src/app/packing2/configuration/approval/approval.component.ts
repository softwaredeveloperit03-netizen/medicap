import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingConfigurations();
  }

  getPendingConfigurations() {
    this.service.get('packing/configuration.php?type=getPendingConfigurations').subscribe(response => {
      this.results = response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  updateConfiguration(status) {
    this.service.get('packing/configuration.php?type=updateConfiguration&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alert('Configuration is Updated Successfuly');
        this.isView = false;
        this.getPendingConfigurations();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  

}
