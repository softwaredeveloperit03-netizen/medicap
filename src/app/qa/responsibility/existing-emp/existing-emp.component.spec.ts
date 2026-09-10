import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExistingEmpComponent } from './existing-emp.component';

describe('ExistingEmpComponent', () => {
  let component: ExistingEmpComponent;
  let fixture: ComponentFixture<ExistingEmpComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ExistingEmpComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ExistingEmpComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
