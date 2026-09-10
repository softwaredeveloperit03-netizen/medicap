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

  ngOnInit() {
  }
  save(data){

    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('rnd/predevelopment.php?type=savePredevelopment',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success(" save successfully");
        data.reset();
        this.router.navigate(['/rnd/predevelopment'])
      }
      else{
        alertify.error("error occured")
      }
    });
  }

}
