import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingRetests();
  }
  
  getPendingRetests() {
    this.service.get('store/packing.php?type=getPendingRetests').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  save(){
    let temp = this.selectedReport;
    this.service.post('store/packing.php?type=saveRetest',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getPendingRetests();
      }else{
        alertify.error('Failed an error occurd,Please try again!');
      }
    });
  }

}
