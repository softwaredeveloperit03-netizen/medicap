import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailypendingComponent } from './dailypending.component';

describe('DailypendingComponent', () => {
  let component: DailypendingComponent;
  let fixture: ComponentFixture<DailypendingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DailypendingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailypendingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
