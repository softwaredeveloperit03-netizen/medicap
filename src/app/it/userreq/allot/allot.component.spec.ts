import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AllotComponent } from './allot.component';

describe('AllotComponent', () => {
  let component: AllotComponent;
  let fixture: ComponentFixture<AllotComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AllotComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AllotComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
