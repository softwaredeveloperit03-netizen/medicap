import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
  results;
  selectedReport=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSpillages();
  }

  getPendingSpillages(){
    this.service.get('store/spillage.php?type=getPendingPackingSpillages').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }

  checkSpillage(status) {
    this.service.get('store/spillage.php?type=checkSpillage&status=' + status + '&id=' + this.selectedReport['id'] ).subscribe(response => {
      if (response['status']){
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingSpillages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
