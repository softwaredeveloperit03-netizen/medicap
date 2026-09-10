import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CleaningcheckingComponent } from './cleaningchecking.component';

describe('CleaningcheckingComponent', () => {
  let component: CleaningcheckingComponent;
  let fixture: ComponentFixture<CleaningcheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CleaningcheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CleaningcheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
