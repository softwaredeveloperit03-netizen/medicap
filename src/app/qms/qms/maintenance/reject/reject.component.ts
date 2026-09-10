
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-reject',
  templateUrl: './reject.component.html',
  styleUrls: ['./reject.component.css']
})
export class RejectComponent implements OnInit {


  isView = false;
  results;

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getRejectedMaintenance();
  }

  getRejectedMaintenance(){
    this.service.get('engineering/maintenance.php?type=getRejectedMaintenance').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status){
    this.service.get('engineering/maintenance.php?type=checkMaintenance&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data updated Successfully!');
        this.isView = false;
        this.getRejectedMaintenance();
      }else{
        alert('Failed an error occured,please try again!');
      }
    });
  }

}
