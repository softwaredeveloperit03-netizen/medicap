import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;
  equipments;
  equipment_type = '';
  frequency = 'once in a week';
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getAwaitingIdentifications()
  }

  getAwaitingIdentifications(){
    this.service.get('qa/calibration.php?type=getAwaitingIdentifications').subscribe(response=>{
      this.results=response;
    });
  }

  save(data){
    if(data.valid)
    this.service.post('qa/calibration.php?type=saveCalibrationIdentification',JSON.stringify(data.value)).subscribe(response=>{
      alert("submitted sucessfully");
      data.reset();
    });
    else{
      alert("not valid");
    }
  }


}
