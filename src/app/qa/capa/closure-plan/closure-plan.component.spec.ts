import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClosurePlanComponent } from './closure-plan.component';

describe('ClosurePlanComponent', () => {
  let component: ClosurePlanComponent;
  let fixture: ComponentFixture<ClosurePlanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ClosurePlanComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ClosurePlanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
