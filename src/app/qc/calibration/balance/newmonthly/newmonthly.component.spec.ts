import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewmonthlyComponent } from './newmonthly.component';

describe('NewmonthlyComponent', () => {
  let component: NewmonthlyComponent;
  let fixture: ComponentFixture<NewmonthlyComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewmonthlyComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewmonthlyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
