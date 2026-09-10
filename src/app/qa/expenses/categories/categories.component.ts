import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-categories',
  templateUrl: './categories.component.html',
  styleUrls: ['./categories.component.css']
})
export class CategoriesComponent implements OnInit {
  results;


  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getExpenses()
  }
  getExpenses(){
    this.service.get('account/expense.php?type=getExpenseCategories').subscribe(response=>{
      this.results=response;
    });
  }

  save(data){
    if(data.valid)
    this.service.post('account/expense.php?type=saveExpenseCategory',JSON.stringify(data.value)).subscribe(response=>{
      data.reset();
      alertify.success("Saved Sucesfully");
    });
    else{
      alertify.error("Not Valid")
    }
  }

}
