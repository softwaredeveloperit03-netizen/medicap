import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-sheduleo1',
  templateUrl: './sheduleo1.component.html',
  styleUrls: ['./sheduleo1.component.css']
})
export class Sheduleo1Component implements OnInit {
   

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
  }
  save(data){
    if(data.valid)
    this.service.post('engineering/nitrogen.php?type=save_replacement',JSON.stringify(data.value)).subscribe(response=>{
      alert("saved succesfully")
      this.router.navigate(['/engineering/nitrogen'])
      data.reset();
    });
    else{
      alert("All filled required");
    }
  }

}
