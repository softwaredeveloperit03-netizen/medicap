import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isNew = false;
  results;

  selectedSampling = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getActiveSamplings();
  }

  getActiveSamplings(){
    this.service.get('qc/sampling/packing.php?type=getActiveSamplings').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedSampling = this.results[index];
    this.isNew = true;
  }

  upgateSample(status){
    this.service.get('qc/sampling/packing.php?type=updateActiveSampling&status=' + status + '&id=' + this.selectedSampling['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.isNew = false;
        this.getActiveSamplings();
      }else{
        alertify.error('Field an error occured,Please try Again!');
      }
    });
  }

}
