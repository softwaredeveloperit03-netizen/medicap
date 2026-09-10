import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  gst;

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getGST();
  }
  getGST(){
    this.service.get('common.php?type=getGST').subscribe(response=>{
      this.gst=response;
    });
  }
  save(data){
    if(data.valid)
    this.service.post('master/general.php?type=saveGeneralMaterial',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        this.router.navigate(['/master/stationary'])
        alertify.success("save Successfully");
      }else{
        alertify.error("Falied:an Error Occured");
      }
    });else{
      alertify.error("all fields are required");
    }
  }

}
