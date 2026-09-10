import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MocdrillComponent } from './mocdrill.component';

describe('MocdrillComponent', () => {
  let component: MocdrillComponent;
  let fixture: ComponentFixture<MocdrillComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MocdrillComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MocdrillComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
