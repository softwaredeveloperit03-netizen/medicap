import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OptdsmeterComponent } from './optdsmeter.component';

describe('OptdsmeterComponent', () => {
  let component: OptdsmeterComponent;
  let fixture: ComponentFixture<OptdsmeterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OptdsmeterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OptdsmeterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
