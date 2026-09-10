import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
  }
  save(data){
    if(!data.valid){
      alertify.error('all field are required');
      return;
    }
    this.service.post('management/unit.php?type=saveUnit',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        this.router.navigate(['/hr/unit'])
        alertify.success("save successfully");
      }else{
        alertify.error("Failed:an Error occures");
      }
    })
  }

}
