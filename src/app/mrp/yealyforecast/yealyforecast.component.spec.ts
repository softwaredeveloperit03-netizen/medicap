import { ComponentFixture, TestBed } from '@angular/core/testing';

import { YealyforecastComponent } from './yealyforecast.component';

describe('YealyforecastComponent', () => {
  let component: YealyforecastComponent;
  let fixture: ComponentFixture<YealyforecastComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ YealyforecastComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(YealyforecastComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
