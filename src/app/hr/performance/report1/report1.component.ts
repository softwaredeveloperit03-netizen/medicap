import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import{Router} from '@angular/router';


@Component({
  selector: 'app-report1',
  templateUrl: './report1.component.html',
  styleUrls: ['./report1.component.css']
})
export class Report1Component implements OnInit {
 

  constructor(private service:DataAccessService,private router : Router) { }

  ngOnInit(): void {
  }
  save(data){
    if(data.valid)
    this.service.post('hr/appraisalchecklist.php?type=save_apprasel',JSON.stringify(data.value)).subscribe(response=>{
      alert("saved succesfully")
      this.router.navigate(['/hr/performance'])
      data.reset();
    });
    else{
      alert("All filled required");
    }
  }

}
