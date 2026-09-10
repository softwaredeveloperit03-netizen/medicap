import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FirstresComponent } from './firstres.component';

describe('FirstresComponent', () => {
  let component: FirstresComponent;
  let fixture: ComponentFixture<FirstresComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FirstresComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FirstresComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
