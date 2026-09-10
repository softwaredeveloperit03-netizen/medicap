import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-maintenance',
  templateUrl: './maintenance.component.html',
  styleUrls: ['./maintenance.component.css']
})
export class MaintenanceComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }
  save(data){
    if(data.valid)
    this.service.post('qa/master.php?type=savelocation',JSON.stringify(data.value)).subscribe(response=>{
      alert("saved succesfully")
      this.router.navigate(['/sales-force/doctor'])
      data.reset();
    });
    else{
      alert("All filled required");
    }
  }

}
