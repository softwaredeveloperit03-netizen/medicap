import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ToiletCleaningComponent } from './toilet-cleaning.component';

describe('ToiletCleaningComponent', () => {
  let component: ToiletCleaningComponent;
  let fixture: ComponentFixture<ToiletCleaningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ToiletCleaningComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ToiletCleaningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
