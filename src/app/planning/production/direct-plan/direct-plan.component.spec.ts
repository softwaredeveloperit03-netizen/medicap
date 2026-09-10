import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DirectPlanComponent } from './direct-plan.component';

describe('DirectPlanComponent', () => {
  let component: DirectPlanComponent;
  let fixture: ComponentFixture<DirectPlanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DirectPlanComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DirectPlanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
