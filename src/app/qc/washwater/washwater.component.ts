import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-washwater',
  templateUrl: './washwater.component.html',
  styleUrls: ['./washwater.component.css']
})
export class WashwaterComponent implements OnInit {
  results;
  selectedResults =[];
  isView = false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getWashwater();
  }
  
  getWashwater(){
    this.service.get('qa/washwater.php?type=getWashwater').subscribe(response=>{
      this.results = response;
    });
  }

  view(index){
    this.selectedResults = this.results[index];
    this.isView = true;
  }

  savewater(data){
    if(!data.valid){
      alertify.error('All Feilds are Required');
      return;
    }
    this.service.post('qa/washwater.php?type=updateWashwater&id=' + this.selectedResults['id'],JSON.stringify(data.value)).subscribe(response=>{
      if(response['status'] == 'success'){
        alertify.success('withdrwa sample send succssfuly');
       this.isView=false;
       this.getWashwater();
      }else{
        alertify.error('some error Occured');
      }
    });
  }
}
