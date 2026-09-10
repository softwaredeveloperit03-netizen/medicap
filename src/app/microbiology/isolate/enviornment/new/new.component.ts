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
    if(!data.valid){
      alertify.error("all field are required");
      return;
    }
    this.service.post('microbiology/isolate.php?type=saveEnvInvestigation',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']='success'){
        alertify.success("Save Successfully");
      }else{
        alertify.error("Failed:an error occured")
      }
    });
  }


}
