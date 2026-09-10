import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }
  save(data){
    if(data.valid)
    this.service.post('rnd/qc/master/indicator.php?type=saveIndicator',JSON.stringify(data.value)).subscribe(response=>{
      alertify.success("submitted succesfully");
      data.reset();
    })
    else{
      alertify.error("enter valid data")
    }
  }
}
