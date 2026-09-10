import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-newteam',
  templateUrl: './newteam.component.html',
  styleUrls: ['./newteam.component.css']
})
export class NewteamComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }
  saveChecklist(data){
    if(data.valid)
    this.service.post('hr/appraisalchecklist.php?type=save_newTeam',JSON.stringify(data.value)).subscribe(response=>{
      alert("saved succesfully")
      this.router.navigate(['/hr/performance/team'])
      data.reset();
    });
    else{
      alert("All filled required");
    }
  }
}
