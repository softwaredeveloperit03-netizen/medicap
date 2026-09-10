import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TechdocdashboardComponent } from './techdocdashboard.component';

describe('TechdocdashboardComponent', () => {
  let component: TechdocdashboardComponent;
  let fixture: ComponentFixture<TechdocdashboardComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TechdocdashboardComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TechdocdashboardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
