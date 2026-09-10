import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-utis',
  templateUrl: './utis.component.html',
  styleUrls: ['./utis.component.css']
})
export class UtisComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }
  save(data){
    if(data.valid)
    this.service.post('qc/lab.php?type=save_new',JSON.stringify(data.value)).subscribe(response=>{
      alert("saved succesfully")
      this.router.navigate(['/store/logins'])
      data.reset();
    });
    else{
      alert("All filled required");
    }
  }
}
