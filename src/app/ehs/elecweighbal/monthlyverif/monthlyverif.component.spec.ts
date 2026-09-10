import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MonthlyverifComponent } from './monthlyverif.component';

describe('MonthlyverifComponent', () => {
  let component: MonthlyverifComponent;
  let fixture: ComponentFixture<MonthlyverifComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MonthlyverifComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MonthlyverifComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
