import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FirefightComponent } from './firefight.component';

describe('FirefightComponent', () => {
  let component: FirefightComponent;
  let fixture: ComponentFixture<FirefightComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FirefightComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FirefightComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
