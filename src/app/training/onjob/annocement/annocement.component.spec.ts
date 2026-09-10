import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AnnocementComponent } from './annocement.component';

describe('AnnocementComponent', () => {
  let component: AnnocementComponent;
  let fixture: ComponentFixture<AnnocementComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AnnocementComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AnnocementComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
