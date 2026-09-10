import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-activity',
  templateUrl: './activity.component.html',
  styleUrls: ['./activity.component.css']
})
export class ActivityComponent implements OnInit {

  isView = false;
  results: any = [];

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getAcceptedRequisitions();
  }

  getAcceptedRequisitions() {
    this.service.get('store/cholinebase.php?type=getAcceptedRequisitions').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  prepareGRN(data){
      if(!data.valid){
        alertify.error("All Fields Are Required !!");
        return;
      }
      let temp = data.value;
      temp['id'] = this.selectedResult['id'];
      this.service.post('store/cholinebase.php?type=saveMixingActivity',JSON.stringify(temp)).subscribe(response =>{
        if(response['status'] == 'success'){
          this.getAcceptedRequisitions();
          this.isView = false;
          alertify.success("Record Save Successfully");
        }else{
          alertify.error(response['msg']);
        }
      })
  }

}
