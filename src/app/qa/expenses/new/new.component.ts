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

  categories;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getExpensesCategories();
  }
  
  getExpensesCategories(){
    this.service.get('account/expense.php?type=getExpenseCategories').subscribe(response=>{
      this.categories=response;
    });
  }

  saveExpenses(data) {
    if(!data.valid) {
      alertify.error('all feilds Are Required');
    }

    this.service.post('account/expense.php?type=saveExpense',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Expenses Registration Successfuly done');
        data.resetForm();
        this.router.navigate(['/account/expenses']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
