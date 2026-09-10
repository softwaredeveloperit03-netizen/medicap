import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WehingComponent } from './wehing.component';

describe('WehingComponent', () => {
  let component: WehingComponent;
  let fixture: ComponentFixture<WehingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WehingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WehingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
