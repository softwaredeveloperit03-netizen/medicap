import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView=false;
  results;
  selectedResults;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPressure();
  }
  getPressure(){
    this.service.get('qa/pressure.php?type=getPendingSections').subscribe(response=>{
      this.results=response;
    });

  }
  proceed(index){
    this.selectedResults=this.results[index];
    this.isView=true;
  }
  save(data){
    if(data.valid)
    this.service.post('qa/pressure.php?type=savePressure',JSON.stringify(data.value)).subscribe(response=>{
      this.getPressure();
      alert("saved sucessfully")
      this.isView=false;
    });
    else{
      alert("not valid data")

    }

  }

}
