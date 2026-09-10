import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InhouseComponent } from './inhouse.component';

describe('InhouseComponent', () => {
  let component: InhouseComponent;
  let fixture: ComponentFixture<InhouseComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InhouseComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InhouseComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
